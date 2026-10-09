<?php
// admin/classes.php -- Manage class schedules and slots
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$courseModel = new Course($db);
$classModel = new ClassSection($db);

$message = '';
$messageType = '';

$editMode = false;
$editClass = null;

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $classModel->delete($id);
        $message = "Class section deleted successfully.";
        $messageType = "success";
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $message = "Cannot delete class section: existing enrollments are linked to it.";
        } else {
            $message = "Error deleting class: " . $e->getMessage();
        }
        $messageType = "danger";
    }
}

// Handle Edit Fetch
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editClass = $classModel->find((int)$_GET['id']);
    if ($editClass) {
        $editMode = true;
    }
}

// Handle Form Submission (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $course_id = (int)($_POST['course_id'] ?? 0);
    $class_code = trim($_POST['class_code'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');
    $instructor = trim($_POST['instructor'] ?? '');
    $slots = isset($_POST['slots']) ? (int)$_POST['slots'] : -1;

    if ($course_id <= 0 || empty($class_code) || empty($schedule) || empty($instructor) || $slots < 0) {
        $message = "All fields are required and slots must be 0 or greater.";
        $messageType = "danger";
    } else {
        try {
            if ($action === 'edit') {
                $id = (int)$_POST['class_id'];
                $classModel->update($id, $course_id, $class_code, $schedule, $instructor, $slots);
                $message = "Class section '$class_code' updated successfully.";
                $messageType = "success";
                $editMode = false;
                $editClass = null;
            } else {
                $classModel->create($course_id, $class_code, $schedule, $instructor, $slots);
                $message = "Class section '$class_code' created successfully.";
                $messageType = "success";
            }
        } catch (Exception $e) {
            $message = "Error saving class: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

$courses = $courseModel->all();
$classes = $classModel->allWithCourse();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Classes</h1>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    <!-- Class Form -->
    <div class="card">
        <h2 class="card-title"><?= $editMode ? 'Edit Class Section' : 'Add New Class Section' ?></h2>
        <?php if (empty($courses)): ?>
            <div class="alert alert-warning">
                Please <a href="courses.php">create a Course</a> first before adding classes.
            </div>
        <?php else: ?>
            <form method="POST" action="classes.php">
                <input type="hidden" name="action" value="<?= $editMode ? 'edit' : 'add' ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="class_id" value="<?= htmlspecialchars($editClass['class_id']) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="course_id">Course *</label>
                    <select id="course_id" name="course_id" class="form-control" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['course_id'] ?>" 
                                <?= ($editMode && $editClass['course_id'] == $c['course_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="class_code">Class Code / Section *</label>
                    <input type="text" id="class_code" name="class_code" class="form-control" 
                           placeholder="e.g. WD-A1, CS101-SEC1" 
                           value="<?= htmlspecialchars($editClass['class_code'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="schedule">Schedule *</label>
                    <input type="text" id="schedule" name="schedule" class="form-control" 
                           placeholder="e.g. Mon/Wed 9:00 AM - 11:00 AM" 
                           value="<?= htmlspecialchars($editClass['schedule'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="instructor">Instructor *</label>
                    <input type="text" id="instructor" name="instructor" class="form-control" 
                           placeholder="e.g. Prof. Alan Turing" 
                           value="<?= htmlspecialchars($editClass['instructor'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="slots">Available Slots *</label>
                    <input type="number" id="slots" name="slots" min="0" class="form-control" 
                           placeholder="e.g. 15" 
                           value="<?= htmlspecialchars($editClass['slots'] ?? '15') ?>" required>
                    <div class="form-text">Remaining seats will automatically decrement when students enroll.</div>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <?= $editMode ? 'Update Class' : 'Save Class' ?>
                    </button>
                    <?php if ($editMode): ?>
                        <a href="classes.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Classes List Table -->
    <div>
        <div class="card">
            <h2 class="card-title">Class Sections (<?= count($classes) ?>)</h2>
            <?php if (empty($classes)): ?>
                <p>No class sections registered yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Course</th>
                                <th>Schedule</th>
                                <th>Instructor</th>
                                <th>Slots</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $cl): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cl['class_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($cl['course_name']) ?></td>
                                    <td><?= htmlspecialchars($cl['schedule']) ?></td>
                                    <td><?= htmlspecialchars($cl['instructor']) ?></td>
                                    <td>
                                        <?php if ((int)$cl['slots'] > 0): ?>
                                            <span class="badge badge-slot-ok"><?= (int)$cl['slots'] ?> Left</span>
                                        <?php else: ?>
                                            <span class="badge badge-slot-full">Full (0)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="classes.php?action=edit&id=<?= $cl['class_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="classes.php?action=delete&id=<?= $cl['class_id'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this class section?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
