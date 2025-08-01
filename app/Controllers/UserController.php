<?php

namespace App\Controllers;

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\UserService;

class UserController
{
    private $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }
    
    public function profile()
    {
        $data = $this->userService->getProfile();
        echo json_encode($data);
    }

    public function register(array $data)
    {
        $result = $this->userService->register($data);

        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }

        echo json_encode(['message' => $result['message']]);
    }

    public function verifyEmail($token) {
        $result = $this->userService->verifyEmail($token);

        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }

        echo json_encode(['message' => $result['message']]);
    }

    public function login(string $username, string $password)
    {
        $result = $this->userService->login($username, $password);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode(["message" => 'Login successful','access_token' => $result['access_token'], 'refresh_token' => $result['refresh_token']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    }

    public function resetPassword(string $oldPassword, string $newPassword) {

        $result = $this->userService->resetPassword($oldPassword, $newPassword);
        
        if  ($result['success']) {
            http_response_code(200);
            echo json_encode(['message' => $result['message']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    }

    public function forgotPassword(string $tc, string $username) {
        $result = $this->userService->forgotPassword($tc, $username);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['message' => $result['message']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    }

    public function resetPasswordWithCode(string $username, string $tc, string $code, string $newPassword) {
        
        $result = $this->userService->resetPasswordWithCode($username, $tc,  $code, $newPassword);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['message' => $result['message']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    }

    public function resetUsername (string $newUsername, string $password) {
        $result = $this->userService->resetUsername($newUsername, $password);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['message' => $result['message']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    }

    public function refreshAccessToken(string $refresh_token) {
        
        $result = $this->userService->refreshAccessToken($refresh_token);
        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['token' => $result['token']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    }
}
