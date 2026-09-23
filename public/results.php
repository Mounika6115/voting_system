<?php
require_once __DIR__ . '/../app/auth.php';
require_login();

$pdo = db();
$user = current_user();

$election = $pdo->query('SELECT * FROM elections ORDER BY id DESC LIMIT 1')->fetch();

$results = [];
$totalVotes = 0;
$hasVoted = false;
$isAdmin = is_admin();

if ($election) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE election_id = ? AND user_id = ?');
    $stmt->execute([(int)$election['id'], $user['id']]);
    $hasVoted = (int)$stmt->fetchColumn() > 0;

    if ($isAdmin || $election['status'] === 'closed' || $hasVoted) {
        $stmt = $pdo->prepare(
            'SELECT c.id, c.name, c.party, c.manifesto, COUNT(v.id) AS vote_count
             FROM candidates c
             LEFT JOIN votes v ON v.candidate_id = c.id AND v.election_id = c.election_id
             WHERE c.election_id = ?
             GROUP BY c.id, c.name, c.party, c.manifesto
             ORDER BY vote_count DESC, c.name ASC'
        );
        $stmt->execute([(int)$election['id']]);
        $results = $stmt->fetchAll();
        $totalVotes = array_sum(array_column($results, 'vote_count'));
    }
}

$canView = $election && ($isAdmin || $election['status'] === 'closed' || $hasVoted);

$title = 'Results — Secure Voting System';
$page = 'results';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Election Results</h1>
  <?php if ($election): ?>
    <p class="muted"><?= e($election['title']) ?> —
      <span class="badge badge-<?= $election['status'] === 'open' ? 'open' : 'closed' ?>">
        <?= e(strtoupper($election['status'])) ?>
      </span>
    </p>
  <?php endif; ?>
</div>

<?php if (!$election): ?>
  <div class="alert alert-error">No election found.</div>
<?php elseif (!$canView): ?>
  <div class="alert alert-error">Results are hidden until you vote or the election closes.</div>
  <a href="vote.php" class="btn btn-primary">Cast Your Vote</a>
<?php else: ?>
  <div class="stat-row">
    <div class="stat"><div class="num"><?= $totalVotes ?></div><div class="lbl">Total Votes</div></div>
    <div class="stat"><div class="num"><?= count($results) ?></div><div class="lbl">Candidates</div></div>
  </div>

  <div class="card">
    <h2>Vote Distribution</h2>
    <table class="table">
      <thead>
        <tr><th>#</th><th>Candidate</th><th>Party</th><th>Votes</th><th style="width:35%">Share</th></tr>
      </thead>
      <tbody>
        <?php if (!$results): ?>
          <tr><td colspan="5" class="muted">No candidates yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($results as $i => $r):
          $count = (int)$r['vote_count'];
          $pct = $totalVotes > 0 ? round($count / $totalVotes * 100) : 0;
        ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= e($r['name']) ?></strong></td>
            <td><?= e($r['party']) ?></td>
            <td><?= $count ?></td>
            <td>
              <div class="bar-wrap">
                <div class="bar" style="width:<?= $pct ?>%"><?= $pct ?>%</div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
