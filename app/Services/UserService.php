<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordReset;
use App\Services\Mail\MailServiceFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../vendor/autoload.php';

class UserService {

    private $userModel;
    private $passwordResetModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->passwordResetModel = new PasswordReset();
    }

    public function register(array $data): array
    {
        // Required field check
        $required = ['name', 'surname', 'username', 'password', 'tc', 'email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "$field field is required"];
            }
        }

        if (strlen($data["tc"]) != 11) {
            return ['success' => false, 'message' => 'TC number must be 11 digits.'];
        }

        if (strlen($data["username"]) < 3) {
            http_response_code(response_code: 401);
            return ['success' => false, 'message' => "Username must be at least 3 characters long."];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(401);
            return['success' => false, 'message' => 'Invalid email address'];
        }
        if ((strlen($data['password'])) > 8) {
            http_response_code(401);
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }


        // Check if username or TC already exists
        if ($this->userModel->findByUsername($data['username'])) {
            return['success' => false, 'message' => 'This username is already taken'];
        }
        elseif ($this->userModel->findByTc($data['tc'])) { 
            http_response_code(409);
            return['success' => false, 'message' => 'A user is already registered with this TC number'];
        }

        // Hash the password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // Register user
        if ($this->userModel->create($data)) {
            $user = $this->userModel->findByUsername($data['username']);

              // Email verification token üret
            $payload = [
                'sub' => $user['id'],
                'exp' => time() + (60 * 60 * 24) // 24 saat geçerli
            ];
            $token = JWT::encode($payload, 'secret_key', 'HS256');

            // Email gönder
            $mailService = MailServiceFactory::create();
            $verificationLink = "https://localhost:8080/verify-email?token={$token}";
            $mailService->send($data['email'], 'Verify Your Email', "Click here to verify: <a href='{$verificationLink}'>Verify</a>");

            return ['success' => true, 'message' => 'Registration successful. Please verify your email.'];
        }

        return ['success' => false, 'message' => 'Registration failed. Please try again later.'];
    }

    public function verifyEmail(string $token): array
    {
        if (!isset($token)) {
            return ['success' => false, 'message' => 'Token is missing'];
        }

        try {
            $payload = JWT::decode($token, new Key('secret_key', 'HS256'));
            $userId = $payload->sub;

            $user = $this->userModel->findById($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            if ($user['verified']) {
                return ['success' => false, 'message' => 'Email already verified'];
            }

            $this->userModel->markAsVerified($userId);

            return ['success' => true, 'message' => 'Email verified successfully'];
        } catch (\Exception $e) 
        {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
    }


    public function login(string $username, string $password):array
    {
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            return['success' => false, 'message' => 'User not found'];
        }

        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            return['success' => false, 'message' => 'Incorrect password!'];
        }

        if (!$user['verified']) {
            return ['success' => false, 'message' => 'Email not verified. Please check your inbox.'];
        }

        // Generate JWT token
        $payload = [
            'iss' => 'localhost',
            'sub' => $user['id'],
            'iat' => time(),
            'exp' => time() + (60 * 60) // Valid for 1 hour
        ];

        $jwt = JWT::encode($payload, 'secret_key', 'HS256'); // secret_key should be stored securely

        return['success' => true, 'token' => $jwt];
    }

    public function getProfile(): array
    {
        $data = $this->userModel->findById($_REQUEST['user_id']);
        return $data;
    }

    public function resetPassword(string $oldPassword, string $newPassword):array {

        $data = $this->userModel->findById($_REQUEST['user_id']);

        if (!$data['verified']) {
            return ['success'=> false,'message'=> 'Email not verified. Please verify your email before resetting the password.'];
        }

        // Required field check
        if (!isset($oldPassword) || !isset($newPassword)) {
            http_response_code(400);
            return ['succes' => false, 'message' => "Fields are required"];
        }

        // Password length check (minimum 8 characters)
        if (strlen($newPassword) < 8) { 
            http_response_code(400);
            return ["succes" => false, 'message' => "Password must be at least 8 characters long!"];
        }

        // Old password verification
        if (!(password_verify($oldPassword, $data['password']))) {
            return ['success'=> false, 'message' => 'Old password is incorrect'];
        }

        // Hash new password
        $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        if ($$this->userModel->resetPassword($newPassword, $data['id'])) {
            http_response_code(201);
            return ['success' => true, 'message' => 'Reset successful'];
        } 
        else {
            http_response_code(500);
            return ['success' => false, 'message' => 'An error occurred during reset'];
        }
    }

    public function forgotPassword(string $tc, string $username):array {

        // Required field check
        if (empty($username) || empty($tc)) {
            return ['success'=> false, 'message' =>  'Fields cannot be empty'];
        }

        $dbdata = $this->userModel->findByTc($tc);

        // Compare username with the one registered with the given TC
        if (!($dbdata['username'] === $username)) { 
            http_response_code(400);
            return ["success"=> false, 'message' => "Information does not match"];
        }

        if (!$dbdata['verified']) {
            return ['success'=> false,'message'=> 'Email not verified. Please verify your email before resetting the password.'];
        }

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); // 6 digits
        $expiresAt = date('Y-m-d H:i:s', time() + (60 * 5)); // Valid for 5 minutes

        $this->passwordResetModel->create($dbdata['id'], $code, $expiresAt);
        

        $mailService = MailServiceFactory::create(); // Create MailService instance

        // Send verification code
        if ($mailService->send($dbdata['email'],"Password Reset Code", "<b>Your Verification Code:</b> {$code}")) {
            return ['success' => true, 'message' => 'Verification code generated and sent to the registered email address'];
        } else {
            return ['success' => false, 'message' => 'Verification code could not be sent'];
        }
    }

    public function resetPasswordWithCode(string $username, string $code, string $newPassword) {
        if (empty($username) || empty($code) || empty($newPassword)) {
            return ['success' => false, 'message'=> 'Fields cannot be empty'];
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            http_response_code(404);
            return ['succes' => false , 'message' => 'User not found'];
        }

        $reset = $this->passwordResetModel->findValidCode($user['id'], $code); // May already be used

        if (!$reset) {
            return ['success' => false, 'message' => 'Code is invalid or expired'];
        }

        $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        if($this->userModel->resetPassword($newPassword, $user['id'])) { 
            $this->passwordResetModel->markAsVerified($reset['id']);
            return ['success' => true, 'message' => 'Password has been reset'];
        }
        else {
            return ['success' => true, 'message' => 'An error occurred during reset'];
        }
    }

    public function resetUsername (string $newUsername, string $password) {

        if (empty($newUsername) || empty($password)) {
            return ['success' => false, 'message'=> 'Fields cannot be empty'];
        }

        $data = $this->userModel->findById($_REQUEST['user_id']);

        // Password verification
        if(!password_verify($password, $data['password'])) {
            return ['success' => false,  'message' => "Password is incorrect"];
        }

        if (!$data['verified']) {
            return ['success'=> false,'message'=> 'Email not verified. Please verify your email before resetting the password.'];
        }

        if (!strlen($newUsername) > 0) { 

            echo json_encode(['error' => "Username cannot be empty"]);
            return;
        }

        if ($this->userModel->resetUsername($newUsername, $data["id"])) {
            return ['success' => true, 'message' => 'Reset successful'];
        }
        else {
            return['success' => false, 'message' => 'An error occurred during reset'];
        }
    }
}