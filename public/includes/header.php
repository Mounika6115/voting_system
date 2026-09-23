<?php
require_once __DIR__ . '/../../app/auth.php';
$page = $page ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Secure Voting System') ?></title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="navbar">
  <div class="container nav-inner">
    <a class="brand" href="index.php">🗳 VoteSecure</a>
    <nav>
      <?php if (is_logged_in()): ?>
        <a href="index.php" class="<?= $page === 'home' ? 'active' : '' ?>">Dashboard</a>
        <a href="vote.php" class="<?= $page === 'vote' ? 'active' : '' ?>">Vote</a>
        <a href="results.php" class="<?= $page === 'results' ? 'active' : '' ?>">Results</a>
        <?php if (is_admin()): ?>
          <a href="admin.php" class="<?= $page === 'admin' ? 'active' : '' ?>">Admin</a>
        <?php endif; ?>
        <span class="user-chip"><?= e($_SESSION['user']['username']) ?></span>
        <a href="logout.php" class="btn btn-outline">Logout</a>
      <?php else: ?>
        <a href="login.php" class="<?= $page === 'login' ? 'active' : '' ?>">Login</a>
        <a href="register.php" class="btn btn-primary">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container main">
