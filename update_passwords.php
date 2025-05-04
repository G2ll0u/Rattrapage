<?php
// filepath: c:\www\Rattrapage\update_passwords.php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Config/Database.php';

use App\Config\Database;

// Connect to database
$db = Database::getInstance();

// Plain text passwords to set based on the current values
$passwords = [
    1 => 'admin123',
    2 => 'manager123',
    3 => 'employee123',
    4 => 'admin456',
    5 => 'admin789',
    6 => 'manager234',
    7 => 'manager345',
    8 => 'manager456',
    9 => 'manager567',
    10 => 'manager678',
    11 => 'employee234',
    12 => 'employee345',
    13 => 'employee456',
    14 => 'employee567',
    15 => 'employee678',
    16 => 'employee789',
    17 => 'employee890',
    18 => 'employee901',
    19 => 'employee012',
    20 => 'employee123',
    21 => 'employee234',
    22 => 'employee345',
    23 => 'employee456'
    // ID 24 already has a hashed password
];

// Update each user password
foreach ($passwords as $userId => $plainPassword) {
    // Hash the password properly
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
    
    // Update in database
    $stmt = $db->prepare("UPDATE user_ SET password = ? WHERE ID_User = ?");
    if ($stmt->execute([$hashedPassword, $userId])) {
        echo "Updated password for user ID: $userId <br>";
    } else {
        echo "Failed to update password for user ID: $userId <br>";
    }
}

echo "Password update complete!";