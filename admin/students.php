<?php
// admin/students.php -- Student recording form (multi-table)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/Student.php';

$repo = new EnrollmentRepository($db);
$classes = new ClassSection($db);
$studentModel = new Student($db);

$message = '';
$messageType = '';
$recordedDetails = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $class_id = (int) ($_POST['class_id'] ?? 0);

    // Validate inputs
    if (empty($full_name)) {
        $message = "Student full name is required.";
        $messageType = "danger";
    } elseif ($class_id <= 0) {
        $message = "Please select a valid class section.";
        $messageType = "danger";
    } else {
        try {
            // Call recordStudent: wraps insert student + insert enrollment + decrement slots in ONE transaction
            $enrollment_id = $repo->recordStudent($full_name, $email, $phone, $class_id);
            $recordedDetails = $repo->findWithDetails($enrollment_id);

            $message = "Success! Student '{$full_name}' was successfully recorded and enrolled into {$recordedDetails['class_code']} ({$recordedDetails['course_name']}). Remaining slots decremented to {$recordedDetails['slots']}.";
            $messageType = "success";

            // Clear form inputs
            $full_name = '';
            $email = '';
            $phone = '';
            $class_id = 0;

        } catch (Exception $e) {
            // Catches "No slots available in the selected class." or any failure
            $message = "Enrollment failed: " . $e->getMessage() . " (Transaction rolled back - no student or enrollment records were created).";
            $messageType = "danger";
        }
    }
}

// Refresh class list to reflect accurate slots after any operation
$classList = $classes->allWithCourse();
$recentStudents = $studentModel->all();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Record Student</h1>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    <!-- Student Recording Form -->
    <div class="card">
        <h2 class="card-title">New Student Registration &amp; Enrollment</h2>
        
        <?php if (empty($classList)): ?>
            <div class="alert alert-warning">
                No classes available. Please <a href="classes.php">create a class section</a> first.
            </div>
        <?php else: ?>
            <form method="POST" action="students.php">
                <div class="form-group">
                    <label class="form-label" for="full_name">Student Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           placeholder="e.g. Maria Santos" 
                           value="<?= htmlspecialchars($full_name ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="e.g. maria.santos@example.com" 
                           value="<?= htmlspecialchars($email ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           placeholder="e.g. 0917-123-4567" 
                           value="<?= htmlspecialchars($phone ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="class_id">Select Class Section *</label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">-- Choose Class Section --</option>
                        <?php foreach ($classList as $cl): ?>
                            <?php $isFull = ((int)$cl['slots'] <= 0); ?>
                            <option value="<?= $cl['class_id'] ?>" 
                                <?= (isset($class_id) && $class_id == $cl['class_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cl['class_code']) ?>: <?= htmlspecialchars($cl['course_name']) ?> 
                                (<?= htmlspecialchars($cl['schedule']) ?>) — 
                                <?= $isFull ? '[FULL - 0 SLOTS]' : '[' . (int)$cl['slots'] . ' slots left]' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Classes marked [FULL - 0 SLOTS] will trigger transaction rollback.</div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Record Student &amp; Enroll (Atomic Transaction)
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Right Side: Class Slot Availability Overview -->
    <div>
        <div class="card">
            <h2 class="card-title">Live Class Availability</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Course</th>
                            <th>Schedule</th>
                            <th>Available Slots</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classList as $cl): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($cl['class_code']) ?></strong></td>
                                <td><?= htmlspecialchars($cl['course_name']) ?></td>
                                <td><?= htmlspecialchars($cl['schedule']) ?></td>
                                <td>
                                    <?php if ((int)$cl['slots'] > 0): ?>
                                        <span class="badge badge-slot-ok"><?= (int)$cl['slots'] ?> Available</span>
                                    <?php else: ?>
                                        <span class="badge badge-slot-full">0 Slots (Full)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="font-size: 0.85rem; margin-top: 0.5rem;">
                Looking to enroll a student who is already registered in the system? 
                <a href="enroll.php">Use the Existing Student Enrollment page &rarr;</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
