<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$adminUser = getenv('ADMIN_USER') ?: 'admin';
$adminPass = getenv('ADMIN_PASS') ?: 'Admin@123';
$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@voting.local';

$stmt = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$adminUser]);

if (!$stmt->fetch()) {
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $ins = db()->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, "admin")');
    $ins->execute([$adminUser, $adminEmail, $hash]);
    echo "Created admin user: $adminUser\n";
} else {
    echo "Admin user already exists\n";
}