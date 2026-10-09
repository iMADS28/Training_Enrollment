<?php
// admin/enrollments.php -- List enrollments (JOINs) & Cancel enrollment
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$repo = new EnrollmentRepository($db);

$message = '';
$messageType = '';

// Handle Cancellation action
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $enrollment_id = (int)$_GET['id'];
    try {
        $repo->cancel($enrollment_id);
        $message = "Enrollment #{$enrollment_id} has been cancelled successfully. 1 class slot has been restored.";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Cancellation failed: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Search and Filter handling (Dynamic Parameterized Queries)
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

// Fetch all enrollments joined with students, classes, and courses
$rows = $repo->allWithDetails($search, $status);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Enrollments</h1>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Search & Filter Bar -->
<div class="card" style="padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
    <form method="GET" action="enrollments.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 220px;">
            <label class="form-label" for="search">Search</label>
            <input type="text" id="search" name="search" class="form-control" 
                   placeholder="Student name, email, course, class..." 
                   value="<?= htmlspecialchars($search) ?>">
        </div>

        <div style="width: 180px;">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active Only</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled Only</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if (!empty($search) || !empty($status)): ?>
                <a href="enrollments.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Enrollments Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 class="card-title" style="margin-bottom: 0; border-bottom: none; padding-bottom: 0;">
            All Enrollments (<?= count($rows) ?> records)
        </h2>
        <div style="display: flex; gap: 0.5rem;">
            <a href="students.php" class="btn btn-primary btn-sm">+ Record New Student</a>
            <a href="enroll.php" class="btn btn-secondary btn-sm">+ Enroll Existing</a>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <p style="padding: 1rem 0;">No enrollment records found matching the criteria.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Enroll ID</th>
                        <th>Student Name &amp; Contact</th>
                        <th>Course</th>
                        <th>Class Section</th>
                        <th>Schedule &amp; Instructor</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>#<strong><?= htmlspecialchars($r['enrollment_id']) ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($r['full_name']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: #64748b;">
                                    <?= htmlspecialchars($r['email'] ?? 'No email') ?> | <?= htmlspecialchars($r['phone'] ?? 'No phone') ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($r['course_code']) ?></strong><br>
                                <span style="font-size: 0.82rem; color: #475569;"><?= htmlspecialchars($r['course_name']) ?></span>
                            </td>
                            <td><strong><?= htmlspecialchars($r['class_code']) ?></strong></td>
                            <td>
                                <?= htmlspecialchars($r['schedule']) ?><br>
                                <span style="font-size: 0.8rem; color: #64748b;">Instructor: <?= htmlspecialchars($r['instructor']) ?></span>
                            </td>
                            <td style="white-space: nowrap; font-size: 0.85rem;">
                                <?= date('M d, Y h:i A', strtotime($r['enrollment_date'])) ?>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'active'): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-cancelled">Cancelled</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <?php if ($r['status'] === 'active'): ?>
                                    <a href="enrollments.php?action=cancel&id=<?= $r['enrollment_id'] ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to cancel enrollment #<?= $r['enrollment_id'] ?>? This will restore 1 class slot.');">
                                        Cancel Enrollment
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.82rem; color: #94a3b8; font-style: italic;">Slots Restored</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
