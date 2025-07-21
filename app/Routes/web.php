<?php

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use Firebase\JWT\JWT;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Controllers/UserController.php'; 
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';



$uri = $_SERVER['REQUEST_URI'];

if ($uri === '/user' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    (new AuthMiddleware)->handle(function () {
        (new UserController)->profile();
    });
}
elseif ($uri === '/login' && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // JSON body'yi al ve diziye dönüştür
    $data = json_decode(file_get_contents("php://input"), true);
    (new UserController)->login($data['username'], $data['password']);
    exit;
}
elseif ($uri === '/register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    (new UserController)->register($data);
    exit;
}
elseif($uri === '/reset-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['newPassword']) || !isset($data['oldPassword'])) {
        http_response_code(400);
        echo json_encode(['error'=> 'Password ve newpassword zorunlu alan']);
        exit;
    }

    (new AuthMiddleware)->handle(function () use ($data) {
        (new UserController)->resetPassword($data['oldPassword'], $data['newPassword']);
    });    
}
elseif($uri === '/reset-username' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    (new AuthMiddleware)->handle(function () use ($data) {
        (new UserController)->resetUsername($data['newUsername'], $data['password']);
    });    

}
elseif($uri === '/forgot-password'&& $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    (new UserController)->forgotPassword($data['tc'], $data['username']);
}
elseif($uri === '/resetPasswordWithCode' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    (new UserController)->resetPasswordWithCode($data['username'],$data['code'], $data['newPassword']);
}
else {
    echo "404 Not Found";
}