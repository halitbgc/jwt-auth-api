<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");


use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$route = $uri . ':' . $method;

// Tarayıcının preflight OPTIONS isteği için erken yanıt ver
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

switch ($route) {
    case '/user:GET':
        (new AuthMiddleware)->handle(function () {
            (new UserController)->profile();
        });
        break;

    case '/login:POST':
        $data = json_decode(file_get_contents("php://input"), true);
        (new UserController)->login($data['username'], $data['password']);
        exit;

    case '/register:POST':
        $data = json_decode(file_get_contents("php://input"), true);
        (new UserController)->register($data);
        exit;
    
    case '/verify-email:GET':
        $token = $_GET['token'];
        (new UserController)->verifyEmail($token);
        exit;

    case '/reset-password:POST':
        $data = json_decode(file_get_contents("php://input"), true);

        (new AuthMiddleware)->handle(function () use ($data) {
            (new UserController)->resetPassword($data['oldPassword'], $data['newPassword']);
        });
        break;

    case '/reset-username:POST':
        $data = json_decode(file_get_contents("php://input"), true);

        (new AuthMiddleware)->handle(function () use ($data) {
            (new UserController)->resetUsername($data['newUsername'], $data['password']);
        });
        break;

    case '/forgot-password:POST':
        $data = json_decode(file_get_contents("php://input"), true);
        (new UserController)->forgotPassword($data['tc'], $data['username']);
        break;

    case '/resetPasswordWithCode:POST':
        $data = json_decode(file_get_contents("php://input"), true);
        (new UserController)->resetPasswordWithCode($data['username'], $data['tc'], $data['code'], $data['newPassword']);
        break;

    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}
