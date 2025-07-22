<?php

namespace App\Models;
use App\Config\Database;
use PDO;

class PasswordReset
{
    private $db;

    public function __construct()
    {
        require_once __DIR__ . '/../config/database.php'; 
        $this->db = Database::getConnection();
    }

    public function create($userId, $code, $expiresAt)
    {
        $stmt = $this->db->prepare("INSERT INTO password_reset_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $code, $expiresAt]);
    }

    public function findValidCode($userId, $code)
    {
        $stmt = $this->db->prepare("SELECT * FROM password_reset_codes WHERE user_id = ? AND code = ? AND expires_at > NOW() AND status = FALSE");
        $stmt->execute([$userId, $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markAsVerified($id)
    {
        $stmt = $this->db->prepare("UPDATE password_reset_codes SET status = TRUE WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
