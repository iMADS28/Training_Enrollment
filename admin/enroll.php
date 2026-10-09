<?php
// admin/enroll.php -- Enroll an EXISTING student into a class
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$repo = new EnrollmentRepository($db);
$studentModel = new Student($db);
$classModel = new ClassSection($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $class_id = (int) ($_POST['class_id'] ?? 0);

    if ($student_id <= 0 || $class_id <= 0) {
        $message = "Please select both an existing student and a class section.";
        $messageType = "danger";
    } else {
        try {
            // Call transactional enroll()
            $enrollment_id = $repo->enroll($student_id, $class_id);
            $details = $repo->findWithDetails($enrollment_id);

            $message = "Success! Student '{$details['full_name']}' was enrolled into {$details['class_code']} ({$details['course_name']}). Available slots decremented to {$details['slots']}.";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Enrollment failed: " . $e->getMessage() . " (Transaction rolled back).";
            $messageType = "danger";
        }
    }
}

$students = $studentModel->all();
$classList = $classModel->allWithCourse();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Enroll Student</h1>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    <!-- Existing Student Enrollment Form -->
    <div class="card">
        <h2 class="card-title">Enroll Student</h2>

        <?php if (empty($students)): ?>
            <div class="alert alert-info">
                No students registered in the system yet. Please <a href="students.php">Record a new student first</a>.
            </div>
        <?php elseif (empty($classList)): ?>
            <div class="alert alert-warning">
                No classes available. Please <a href="classes.php">create a class section</a> first.
            </div>
        <?php else: ?>
            <form method="POST" action="enroll.php">
                <div class="form-group">
                    <label class="form-label" for="student_id">Select Existing Student *</label>
                    <select id="student_id" name="student_id" class="form-control" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['student_id'] ?>">
                                <?= htmlspecialchars($s['full_name']) ?> (ID: #<?= $s['student_id'] ?><?= !empty($s['email']) ? ' - ' . htmlspecialchars($s['email']) : '' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="class_id">Select Class Section *</label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">-- Choose Class Section --</option>
                        <?php foreach ($classList as $cl): ?>
                            <?php $isFull = ((int)$cl['slots'] <= 0); ?>
                            <option value="<?= $cl['class_id'] ?>">
                                <?= htmlspecialchars($cl['class_code']) ?>: <?= htmlspecialchars($cl['course_name']) ?> 
                                (<?= htmlspecialchars($cl['schedule']) ?>) — 
                                <?= $isFull ? '[FULL - 0 SLOTS]' : '[' . (int)$cl['slots'] . ' slots left]' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">System prevents duplicate active enrollments and checks slot availability atomically.</div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Confirm Enrollment (Transactional)
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Right Side: Class Slots Status -->
    <div>
        <div class="card">
            <h2 class="card-title">Class Capacities</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Course</th>
                            <th>Instructor</th>
                            <th>Available Slots</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classList as $cl): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($cl['class_code']) ?></strong></td>
                                <td><?= htmlspecialchars($cl['course_name']) ?></td>
                                <td><?= htmlspecialchars($cl['instructor']) ?></td>
                                <td>
                                    <?php if ((int)$cl['slots'] > 0): ?>
                                        <span class="badge badge-slot-ok"><?= (int)$cl['slots'] ?> Left</span>
                                    <?php else: ?>
                                        <span class="badge badge-slot-full">Full (0)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
