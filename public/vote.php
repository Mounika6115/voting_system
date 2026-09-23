<?php
require_once __DIR__ . '/../app/auth.php';
require_login();

$pdo = db();
$user = current_user();

$election = $pdo->query('SELECT * FROM elections ORDER BY id DESC LIMIT 1')->fetch();

if (!$election) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'No election found.'];
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE election_id = ? AND user_id = ?');
$stmt->execute([(int)$election['id'], $user['id']]);
$hasVoted = (int)$stmt->fetchColumn() > 0;

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session. Please try again.';
    } elseif ($election['status'] !== 'open') {
        $errors[] = 'This election is closed.';
    } elseif ($hasVoted) {
        $errors[] = 'You have already voted.';
    } else {
        $candidateId = (int)($_POST['candidate_id'] ?? 0);

        $check = $pdo->prepare('SELECT id FROM candidates WHERE id = ? AND election_id = ?');
        $check->execute([$candidateId, (int)$election['id']]);
        if (!$check->fetch()) {
            $errors[] = 'Please select a valid candidate.';
        } else {
            try {
                $pdo->beginTransaction();
                $ins = $pdo->prepare('INSERT INTO votes (election_id, user_id, candidate_id) VALUES (?, ?, ?)');
                $ins->execute([(int)$election['id'], $user['id'], $candidateId]);
                $pdo->commit();
                $hasVoted = true;
                $success = 'Your vote has been recorded successfully. Thank you!';
            } catch (PDOException $e) {
                $pdo->rollBack();
                if ($e->getCode() === '23000') {
                    $errors[] = 'You have already voted.';
                    $hasVoted = true;
                } else {
                    $errors[] = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}

$candidates = [];
if ($election['status'] === 'open' && !$hasVoted) {
    $stmt = $pdo->prepare('SELECT * FROM candidates WHERE election_id = ? ORDER BY name');
    $stmt->execute([(int)$election['id']]);
    $candidates = $stmt->fetchAll();
}

$title = 'Vote — Secure Voting System';
$page = 'vote';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Cast Your Vote</h1>
  <p class="muted"><?= e($election['title']) ?> —
    <span class="badge badge-<?= $election['status'] === 'open' ? 'open' : 'closed' ?>">
      <?= e(strtoupper($election['status'])) ?>
    </span>
  </p>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-error"><?= e($err) ?></div>
<?php endforeach; ?>
<?php if ($success): ?>
  <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($hasVoted && !$success): ?>
  <div class="alert alert-success">You have already cast your vote in this election.</div>
  <a href="results.php" class="btn btn-outline">View Results</a>
<?php elseif ($election['status'] !== 'open'): ?>
  <div class="alert alert-error">This election is currently closed.</div>
<?php elseif (!$candidates): ?>
  <div class="alert alert-error">No candidates available for this election.</div>
<?php else: ?>
  <form method="post" action="vote.php" id="voteForm">
    <?= csrf_field() ?>
    <div class="grid grid-2">
      <?php foreach ($candidates as $c): ?>
        <label class="candidate-card" for="cand<?= (int)$c['id'] ?>">
          <input type="radio" name="candidate_id" id="cand<?= (int)$c['id'] ?>"
                 value="<?= (int)$c['id'] ?>" required style="display:none">
          <h3><?= e($c['name']) ?></h3>
          <div class="party"><?= e($c['party']) ?></div>
          <?php if ($c['manifesto']): ?>
            <div class="manifesto"><?= e($c['manifesto']) ?></div>
          <?php endif; ?>
        </label>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary mt">Submit Vote</button>
    <p class="muted mt">Once submitted, your vote cannot be changed.</p>
  </form>

  <script>
    document.querySelectorAll('.candidate-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        card.querySelector('input[type=radio]').checked = true;
      });
    });
    document.getElementById('voteForm').addEventListener('submit', e => {
      if (!document.querySelector('input[name=candidate_id]:checked')) {
        e.preventDefault();
        alert('Please select a candidate.');
      } else if (!confirm('Confirm your vote? This cannot be undone.')) {
        e.preventDefault();
      }
    });
  </script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
