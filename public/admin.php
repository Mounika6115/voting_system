<?php
require_once __DIR__ . '/../app/auth.php';
require_admin();

$pdo = db();
$errors = [];
$success = null;

$election = $pdo->query('SELECT * FROM elections ORDER BY id DESC LIMIT 1')->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_candidate') {
            $electionId = (int)($_POST['election_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $party = trim($_POST['party'] ?? '');
            $manifesto = trim($_POST['manifesto'] ?? '');

            if ($name === '' || $party === '') {
                $errors[] = 'Candidate name and party are required.';
            } elseif (!$election) {
                $errors[] = 'No election exists.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO candidates (election_id, name, party, manifesto) VALUES (?, ?, ?, ?)');
                $stmt->execute([(int)$election['id'], $name, $party, $manifesto ?: null]);
                $success = 'Candidate added.';
            }
        } elseif ($action === 'delete_candidate') {
            $id = (int)($_POST['candidate_id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM candidates WHERE id = ?');
            $stmt->execute([$id]);
            $success = 'Candidate removed.';
        } elseif ($action === 'toggle_election') {
            $newStatus = $election['status'] === 'open' ? 'closed' : 'open';
            $stmt = $pdo->prepare('UPDATE elections SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, (int)$election['id']]);
            $success = 'Election is now ' . $newStatus . '.';
            $election['status'] = $newStatus;
        } elseif ($action === 'add_election') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if ($title === '') {
                $errors[] = 'Election title is required.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO elections (title, description) VALUES (?, ?)');
                $stmt->execute([$title, $description]);
                $success = 'Election created.';
                $election = $pdo->query('SELECT * FROM elections ORDER BY id DESC LIMIT 1')->fetch();
            }
        } elseif ($action === 'close_all') {
            $pdo->exec("UPDATE elections SET status = 'closed'");
            $success = 'All elections closed.';
            $election = $pdo->query('SELECT * FROM elections ORDER BY id DESC LIMIT 1')->fetch();
        }
    }
}

$candidates = [];
if ($election) {
    $stmt = $pdo->prepare('SELECT * FROM candidates WHERE election_id = ? ORDER BY name');
    $stmt->execute([(int)$election['id']]);
    $candidates = $stmt->fetchAll();
}

$allElections = $pdo->query('SELECT e.*, (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS vote_count FROM elections e ORDER BY e.id DESC')->fetchAll();
$allUsers = $pdo->query('SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();

$title = 'Admin — Secure Voting System';
$page = 'admin';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Admin Panel</h1>
  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
  <?php endif; ?>
</div>

<div class="grid grid-2">
  <div class="card">
    <h2>Elections</h2>
    <table class="table">
      <thead><tr><th>Title</th><th>Status</th><th>Votes</th></tr></thead>
      <tbody>
        <?php foreach ($allElections as $el): ?>
          <tr>
            <td><?= e($el['title']) ?></td>
            <td><span class="badge badge-<?= $el['status'] === 'open' ? 'open' : 'closed' ?>"><?= e(strtoupper($el['status'])) ?></span></td>
            <td><?= (int)$el['vote_count'] ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$allElections): ?>
          <tr><td colspan="3" class="muted">No elections.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($election): ?>
      <form method="post" class="mt">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="toggle_election">
        <?php if ($election['status'] === 'open'): ?>
          <button type="submit" class="btn btn-danger" onclick="return confirm('Close this election? Votes will be finalized.')">Close Election</button>
        <?php else: ?>
          <button type="submit" class="btn btn-success">Reopen Election</button>
        <?php endif; ?>
      </form>
    <?php endif; ?>

    <hr style="margin:1.25rem 0; border:none; border-top:1px solid var(--border);">
    <h2>Create Election</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_election">
      <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="2"></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Create Election</button>
    </form>
  </div>

  <div class="card">
    <h2>Candidates <?= $election ? '— ' . e($election['title']) : '' ?></h2>
    <table class="table">
      <thead><tr><th>Name</th><th>Party</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($candidates as $c): ?>
          <tr>
            <td><?= e($c['name']) ?></td>
            <td><?= e($c['party']) ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Remove candidate?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_candidate">
                <input type="hidden" name="candidate_id" value="<?= (int)$c['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$candidates): ?>
          <tr><td colspan="3" class="muted">No candidates yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <hr style="margin:1.25rem 0; border:none; border-top:1px solid var(--border);">
    <h2>Add Candidate</h2>
    <?php if (!$election): ?>
      <p class="muted">Create an election first.</p>
    <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_candidate">
        <div class="form-group">
          <label>Name</label>
          <input type="text" name="name" required maxlength="100">
        </div>
        <div class="form-group">
          <label>Party / Group</label>
          <input type="text" name="party" required maxlength="100">
        </div>
        <div class="form-group">
          <label>Manifesto (optional)</label>
          <textarea name="manifesto" rows="2" maxlength="500"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Add Candidate</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h2>Registered Users (<?= count($allUsers) ?>)</h2>
  <table class="table">
    <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th></tr></thead>
    <tbody>
      <?php foreach ($allUsers as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><?= e($u['username']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-open' : 'badge-closed' ?>"><?= e(strtoupper($u['role'])) ?></span></td>
          <td><?= e($u['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
