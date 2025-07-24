<?php

namespace App\Models;
use App\Config\Database;

use PDO;

class User
{
    private $db;

    public function __construct()
    {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::getConnection();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("INSERT INTO users (name, surname, username, password, tc, email) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'],
            $data['surname'],
            $data['username'],
            $data['password'],
            $data['tc'],
            $data['email'],
            ]);
    }  

    public function markAsVerified(int $id): bool{
        $stmt = $this->db->prepare("UPDATE users SET verified = 't' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function resetPassword(string $newPassword, int $id): bool
    {
        // Update password
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");

        $result = $stmt->execute([
            $newPassword,
            $id
        ]);
    
        return $result;
    }

    public function resetUsername(string $newUsername, int $id): bool {
        $stmt = $this->db->prepare("UPDATE users SET username = ? WHERE id = ?");
        $result = $stmt->execute(
            [$newUsername, $id]
        );
        return $result;
    }

    public function findById($id):mixed
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByUsername(string $username):mixed
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByTc(string $tc) :mixed
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE tc = ?");
        $stmt->execute([$tc]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
