<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\PasswordReset;
use App\Services\MailService;
use Firebase\JWT\JWT;


require_once __DIR__ . '/../Services/MailService.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/PasswordReset.php';

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
        // Zorunlu alan kontrolü
        $required = ['name', 'surname', 'username', 'password', 'tc', 'email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "$field alanı zorunludur"]);
                return;
            }
        }
        if (strlen($data["tc"]) != 11) {
            http_response_code(response_code: 401);
            echo json_encode(['error' => "TC no 11 haneli olmalidir."]);
            return;
        }
        if (strlen($data["username"]) < 3) {
            http_response_code(response_code: 401);
            echo json_encode(['error' => "username 3 uzunlugunda olmali."]);
            return;
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(401);
            echo json_encode(['error' => 'Geçersiz e-posta adresi']);
            return;
        }


        $userModel = new User();

        // Aynı username var mı kontrolü ve tc
        if ($userModel->findByUsername($data['username'])) {
            http_response_code(409);
            echo json_encode(['error' => 'Bu kullanıcı adı zaten alınmış']);
            return;
        }
        elseif ($userModel->findByTc($data['tc'])) { 
            http_response_code(409);
            echo json_encode(['error' => 'Bu tc ile kullanici kayitli']);
            return;
        }

        // Şifreyi hash'le 
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // Kayıt işlemi
        if ($userModel->create($data)) {
            http_response_code(201);
            echo json_encode(['message' => 'Kayıt başarılı']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Kayıt sırasında bir hata oluştu']);
        }
    }

    public function login(string $username, string $password)
    {
        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Kullanıcı bulunamadı']);
            return;
        }
        
        
        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Sfre hatali!']);
            return;
        }
        

        $userId = $user['id'];

        $payload = [
            'iss' => 'localhost',
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + (60 * 60) // 1 saat geçerli
        ];

        $jwt = JWT::encode($payload, 'secret_key', 'HS256');
        echo json_encode(['token' => $jwt]);;
    }

    public function resetPassword(string $oldPassword, string $newPassword) {
        $user = new User();
        $data = $user->findById($_REQUEST['user_id']);

        // Zorunlu alan kontrolü
        $required = ['newPassword', 'oldPassword'];
        if (!isset($oldPassword) || !isset($newPassword)) {
            http_response_code(400);
            echo json_encode(['error' => "Alanlar zorunludur"]);
            return;
        }

        // sifre uzunluk kontrolu
        if (strlen($newPassword) < 8) { 
            http_response_code(400);
            echo json_encode(["error"=> "Sifre en az 8 karakter uzunlugunda olmali!"]);
            return;
        }

        // Sifre kontrol
        if (!(password_verify($oldPassword, $data['password']))) {
            echo json_encode(['error'=> 'Oldpassword hatali']);
            return;
        }

        //hash
        $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // sifre guncelleme
        if ($user->resetPassword($newPassword, $data['id'])) {
            http_response_code(201);
            echo json_encode(['message' => 'Reset başarılı']);
        } 
        else {
            http_response_code(500);
            echo json_encode(['error' => 'Reset sirasinda bir hata olustu']);
        }
    }

    public function forgotPassword(string $tc, string $username) {

        // Zorunlu alan kontrolü
        if (empty($username) || empty($tc)) {
            http_response_code(400);
            echo json_encode(['error'=> 'Bos olamaz']);
            return;
        }

        
        $user = new User();
        $dbdata = $user->findByTc($tc);
        // tc ile username kiyasla
        if (!($dbdata['username'] === $username)) { 
            http_response_code(400);
            echo json_encode(["error"=> "Bilgiler eslesmiyor "]);
            return;
        }
        

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); // 6 haneli
        $expiresAt = date('Y-m-d H:i:s', time() + (60 * 5)); // 5 dk geçerli

        $resetModel = new PasswordReset();
        $resetModel->create($dbdata['id'], $code, $expiresAt);

        // kodu gonderme
        if (MailService::sendCode($dbdata['email'], $code)) {
            echo json_encode([
                'message' => 'Doğrulama kodu üretildi ve kayitli mail adresine gonderildi'
            ]);
        } else {
            echo json_encode([
                'message' => 'Doğrulama kodu gonderilemedi'
            ]);
        }
    }

    public function resetPasswordWithCode(string $username, string $code, string $newPassword) {
        if (empty($username) || empty($code) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(['error'=> 'Alanlar bos olamaz']);
            return;
        }

        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Kullanıcı bulunamadı']);
            return;
        }

        $resetModel = new PasswordReset();
        $reset = $resetModel->findValidCode($user['id'], $code); // kullanilmista olabilir

        if (!$reset) {
            http_response_code(400);
            echo json_encode(['error' => 'Kod geçersiz ya da süresi dolmuş']);
            return;
        }

        $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        if($userModel->resetPassword($newPassword, $user['id'])) { 
            http_response_code(200);
            $resetModel->markAsVerified($reset['id']);
            echo json_encode(['message' => 'Sifre resetlendi']);
            return;
        }
        else {
            http_response_code(500);
            echo json_encode(['error' => 'Reset sirasinda bir hata olustu']);
        }
    }

    

    public function resetUsername (string $newUsername, string $password) {
        $user = new User();
        $data = $user->findById($_REQUEST['user_id']);

        if (empty($newUsername) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error'=> 'Alanlar bos olamaz']);
        }

        // sifre kontol
        if(!password_verify($password, $data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => "Password hatali"]);
            return;
        }

        if (!strlen($newUsername) > 0) { 
            http_response_code(400);
            echo json_encode(['error' => "Username bos olamaz hatali"]);
            return;
        }

        if ($user->resetUsername($newUsername, $data["id"])) {
            http_response_code(201);
            echo json_encode(['message' => 'Reset başarılı']);
        }
        else {
            http_response_code(500);
            echo json_encode(['error' => 'Reset sirasinda bir hata olustu']);
        }
    }

}