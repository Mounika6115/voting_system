<?php
require_once __DIR__ . '/../app/auth.php';
require_login();

$pdo = db();
$user = current_user();

$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalCandidates = (int)$pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn();

$election = $pdo->query('SELECT * FROM elections ORDER BY id DESC LIMIT 1')->fetch();

$hasVoted = false;
if ($election) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE election_id = ? AND user_id = ?');
    $stmt->execute([(int)$election['id'], $user['id']]);
    $hasVoted = (int)$stmt->fetchColumn() > 0;
}

$title = 'Dashboard — Secure Voting System';
$page = 'home';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Welcome, <?= e($user['username']) ?> 👋</h1>
  <p class="muted">Secure, verifiable and simple elections for your community.</p>
</div>

<div class="stat-row">
  <div class="stat"><div class="num"><?= $totalUsers ?></div><div class="lbl">Registered Voters</div></div>
  <div class="stat"><div class="num"><?= $totalCandidates ?></div><div class="lbl">Candidates</div></div>
  <div class="stat"><div class="num"><?= $election ? e(strtoupper($election['status'])) : '—' ?></div><div class="lbl">Election Status</div></div>
</div>

<div class="grid grid-2">
  <div class="card">
    <h2>Current Election</h2>
    <?php if ($election): ?>
      <p><strong><?= e($election['title']) ?></strong></p>
      <p class="muted"><?= e($election['description']) ?></p>
      <p class="mt">
        <span class="badge badge-<?= $election['status'] === 'open' ? 'open' : 'closed' ?>">
          <?= e(strtoupper($election['status'])) ?>
        </span>
      </p>
      <?php if ($election['status'] === 'open' && !$hasVoted): ?>
        <p class="mt"><a href="vote.php" class="btn btn-primary">Cast Your Vote</a></p>
      <?php elseif ($hasVoted): ?>
        <p class="mt alert alert-success">You have already voted in this election.</p>
      <?php endif; ?>
      <p class="mt"><a href="results.php" class="btn btn-outline">View Results</a></p>
    <?php else: ?>
      <p class="muted">No election available yet.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Your Security</h2>
    <p class="muted">This system protects your vote with:</p>
    <ul style="margin:.8rem 0 0 1.2rem; color:var(--muted); font-size:.93rem; line-height:1.7;">
      <li>Encrypted password hashing (bcrypt)</li>
      <li>CSRF protection on all forms</li>
      <li>Prepared statements (SQL injection safe)</li>
      <li>One vote per user, enforced in the database</li>
      <li>HTTP-only session cookies</li>
    </ul>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
