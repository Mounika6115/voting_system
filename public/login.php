<?php
require_once __DIR__ . '/../app/auth.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $errors[] = 'Username and password are required.';
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int)$user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                ];
                header('Location: index.php');
                exit;
            }
            $errors[] = 'Invalid credentials.';
            usleep(300000);
        }
    }
}

$title = 'Login — Secure Voting System';
$page = 'login';
require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:480px;margin:0 auto;">
  <h1>Login</h1>
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>
  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post" action="login.php">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="username">Username or Email</label>
      <input type="text" id="username" name="username" required autofocus>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Login</button>
  </form>
  <p class="muted mt">No account? <a href="register.php">Register</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
