<?php

namespace App\Controllers;

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Models\PasswordReset;
use App\Services\MailService;
use Firebase\JWT\JWT;

class UserController
{
    public function profile()
    {
        $user = new User();
        $data = $user->findById($_REQUEST['user_id']);
        echo json_encode($data);
    }

    public function register(array $data)
    {
        // Required field check
        $required = ['name', 'surname', 'username', 'password', 'tc', 'email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "$field field is required"]);
                return;
            }
        }
        if (strlen($data["tc"]) != 11) {
            http_response_code(response_code: 401);
            echo json_encode(['error' => "TC number must be 11 digits."]);
            return;
        }
        if (strlen($data["username"]) < 3) {
            http_response_code(response_code: 401);
            echo json_encode(['error' => "Username must be at least 3 characters long."]);
            return;
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid email address']);
            return;
        }
        if ((strlen($data['password'])) > 8) {
            http_response_code(401);
            echo json_encode(['error'=> 'Password must be at least 8 characters']);
            return;
        }

        $userModel = new User();

        // Check if username or TC already exists
        if ($userModel->findByUsername($data['username'])) {
            http_response_code(409);
            echo json_encode(['error' => 'This username is already taken']);
            return;
        }
        elseif ($userModel->findByTc($data['tc'])) { 
            http_response_code(409);
            echo json_encode(['error' => 'A user is already registered with this TC number']);
            return;
        }

        // Hash the password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // Register user
        if ($userModel->create($data)) {
            http_response_code(201);
            echo json_encode(['message' => 'Registration successful']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'An error occurred during registration']);
        }
    }

    public function login(string $username, string $password)
    {
        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Incorrect password!']);
            return;
        }

        $userId = $user['id'];

        $payload = [
            'iss' => 'localhost',
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + (60 * 60) // Valid for 1 hour
        ];

        $jwt = JWT::encode($payload, 'secret_key', 'HS256');
        echo json_encode(['token' => $jwt]);;
    }

    public function resetPassword(string $oldPassword, string $newPassword) {

        $user = new User();
        $data = $user->findById($_REQUEST['user_id']);

        // Required field check
        if (!isset($oldPassword) || !isset($newPassword)) {
            http_response_code(400);
            echo json_encode(['error' => "Fields are required"]);
            return;
        }

        // Password length check (minimum 8 characters)
        if (strlen($newPassword) < 8) { 
            http_response_code(400);
            echo json_encode(["error"=> "Password must be at least 8 characters long!"]);
            return;
        }

        // Old password verification
        if (!(password_verify($oldPassword, $data['password']))) {
            echo json_encode(['error'=> 'Old password is incorrect']);
            return;
        }

        // Hash new password
        $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        if ($user->resetPassword($newPassword, $data['id'])) {
            http_response_code(201);
            echo json_encode(['message' => 'Reset successful']);
        } 
        else {
            http_response_code(500);
            echo json_encode(['error' => 'An error occurred during reset']);
        }
    }

    public function forgotPassword(string $tc, string $username) {

        // Required field check
        if (empty($username) || empty($tc)) {
            http_response_code(400);
            echo json_encode(['error'=> 'Fields cannot be empty']);
            return;
        }

        $user = new User();
        $dbdata = $user->findByTc($tc);
        // Compare username with the one registered with the given TC
        if (!($dbdata['username'] === $username)) { 
            http_response_code(400);
            echo json_encode(["error"=> "Information does not match"]);
            return;
        }

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); // 6 digits
        $expiresAt = date('Y-m-d H:i:s', time() + (60 * 5)); // Valid for 5 minutes

        $resetModel = new PasswordReset();
        $resetModel->create($dbdata['id'], $code, $expiresAt);

        // Send verification code
        if (MailService::sendCode($dbdata['email'], $code)) {
            echo json_encode([
                'message' => 'Verification code generated and sent to the registered email address'
            ]);
        } else {
            echo json_encode([
                'message' => 'Verification code could not be sent'
            ]);
        }
    }

    public function resetPasswordWithCode(string $username, string $code, string $newPassword) {
        if (empty($username) || empty($code) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(['error'=> 'Fields cannot be empty']);
            return;
        }

        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        $resetModel = new PasswordReset();
        $reset = $resetModel->findValidCode($user['id'], $code); // May already be used

        if (!$reset) {
            http_response_code(400);
            echo json_encode(['error' => 'Code is invalid or expired']);
            return;
        }

        $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        if($userModel->resetPassword($newPassword, $user['id'])) { 
            http_response_code(200);
            $resetModel->markAsVerified($reset['id']);
            echo json_encode(['message' => 'Password has been reset']);
            return;
        }
        else {
            http_response_code(500);
            echo json_encode(['error' => 'An error occurred during reset']);
        }
    }

    public function resetUsername (string $newUsername, string $password) {
        $user = new User();
        $data = $user->findById($_REQUEST['user_id']);

        if (empty($newUsername) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error'=> 'Fields cannot be empty']);
        }

        // Password verification
        if(!password_verify($password, $data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => "Password is incorrect"]);
            return;
        }

        if (!strlen($newUsername) > 0) { 
            http_response_code(400);
            echo json_encode(['error' => "Username cannot be empty"]);
            return;
        }

        if ($user->resetUsername($newUsername, $data["id"])) {
            http_response_code(201);
            echo json_encode(['message' => 'Reset successful']);
        }
        else {
            http_response_code(500);
            echo json_encode(['error' => 'An error occurred during reset']);
        }
    }

}
