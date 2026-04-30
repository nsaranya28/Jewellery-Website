<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

// Handle Status Update
if (isset($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $pdo->prepare("UPDATE contacts SET status='read' WHERE id=?")->execute([$id]);
    flashMessage('success', 'Message marked as read.');
    header('Location: contacts.php'); exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM contacts WHERE id=?")->execute([$id]);
    flashMessage('success', 'Message deleted.');
    header('Location: contacts.php'); exit;
}

$contacts = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
$adminTitle = 'Contact Inquiries';
include 'includes/header.php';
?>

<div class="admin-table-wrap">
  <div class="admin-table-head">
    <h3>✉️ Contact Inquiries (<?= count($contacts) ?>)</h3>
  </div>
  <table>
    <thead>
      <tr>
        <th>Status</th>
        <th>From</th>
        <th>Inquiry Details</th>
        <th>Received At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($contacts)): ?>
      <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--gray);">No messages received yet.</td></tr>
      <?php else: ?>
        <?php foreach ($contacts as $c): ?>
        <tr style="<?= $c['status'] === 'new' ? 'background: rgba(201,162,39,0.03);' : '' ?>">
          <td>
            <span class="status-badge <?= $c['status'] === 'new' ? 'status-confirmed' : 'status-delivered' ?>" style="font-size:10px;">
              <?= strtoupper($c['status']) ?>
            </span>
          </td>
          <td>
            <div style="font-weight:600; color: var(--dark);"><?= safeHtml($c['name']) ?></div>
            <div style="font-size:12px; color: var(--gray);"><?= safeHtml($c['email']) ?></div>
          </td>
          <td style="max-width: 400px; white-space: normal;">
            <div style="font-size: 11px; color: var(--gold-dark); text-transform: uppercase; font-weight: 700; margin-bottom: 4px;"><?= safeHtml($c['subject']) ?></div>
            <div style="font-size: 14px; line-height: 1.5; color: var(--charcoal);"><?= nl2br(safeHtml($c['message'])) ?></div>
          </td>
          <td style="font-size: 12px; color: var(--gray);">
            <div><?= date('d M Y', strtotime($c['created_at'])) ?></div>
            <div style="font-size: 11px; opacity: 0.7;"><?= date('h:i A', strtotime($c['created_at'])) ?></div>
          </td>
          <td>
            <div style="display: flex; gap: 10px;">
              <?php if ($c['status'] === 'new'): ?>
                <a href="?mark_read=<?= $c['id'] ?>" class="btn btn-sm btn-gold" title="Mark as Read" style="padding: 6px 12px; font-size: 11px;">
                  <i class="fas fa-check-double"></i> Read
                </a>
              <?php endif; ?>
              <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm" style="color: var(--red); border: 1px solid var(--red); padding: 6px 12px; font-size: 11px;" onclick="return confirm('Delete this message?')">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include 'includes/footer.php'; ?>
