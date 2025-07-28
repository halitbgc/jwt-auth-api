<?php
namespace App\Models;
use App\Config\Database;
use PDO;

class RefreshToken
{
    private $db;

    public function __construct()
    {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::getConnection();
    }

    public function store($userId, $token, $expiresAt)
    {
        $stmt = $this->db->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $token, $expiresAt]);
    }

    public function findValid($token)
    {
        $stmt = $this->db->prepare("SELECT * FROM refresh_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
