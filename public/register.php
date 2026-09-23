<?php
require_once __DIR__ . '/../app/auth.php';

$errors = [];
$old = ['username' => '', 'email' => ''];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session. Please try again.';
    } else {
        $old['username'] = trim($_POST['username'] ?? '');
        $old['email'] = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $old['username'])) {
            $errors[] = 'Username must be 3-50 chars (letters, numbers, _).';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$old['username'], $old['email']]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, "voter")');
                try {
                    $ins->execute([$old['username'], $old['email'], $hash]);
                    $done = true;
                } catch (PDOException $e) {
                    if (in_array($e->errorInfo[1] ?? null, [1062, 23000], true)) {
                        $errors[] = 'Username or email already exists.';
                    } else {
                        $errors[] = 'Something went wrong. Please try again.';
                    }
                }
                if ($done) {
                    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Registration successful. Please login.'];
                    header('Location: login.php');
                    exit;
                }
            }
        }
    }
}

$title = 'Register — Secure Voting System';
$page = 'register';
require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:480px;margin:0 auto;">
  <h1>Create Account</h1>
  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post" action="register.php" autocomplete="off">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?= e($old['username']) ?>" required>
    </div>
    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" minlength="8" required>
    </div>
    <div class="form-group">
      <label for="confirm">Confirm Password</label>
      <input type="password" id="confirm" name="confirm" minlength="8" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Register</button>
  </form>
  <p class="muted mt">Already have an account? <a href="login.php">Login</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
