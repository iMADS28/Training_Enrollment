<?php
// admin/courses.php -- Manage courses (CRUD)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Course.php';

$courseModel = new Course($db);
$message = '';
$messageType = '';

$editMode = false;
$editCourse = null;

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $courseModel->delete($id);
        $message = "Course deleted successfully.";
        $messageType = "success";
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $message = "Cannot delete course: active classes are assigned to it.";
        } else {
            $message = "Error deleting course: " . $e->getMessage();
        }
        $messageType = "danger";
    }
}

// Handle Edit Fetch
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editCourse = $courseModel->find((int)$_GET['id']);
    if ($editCourse) {
        $editMode = true;
    }
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $code = trim($_POST['course_code'] ?? '');
    $name = trim($_POST['course_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($code) || empty($name)) {
        $message = "Course Code and Course Name are required.";
        $messageType = "danger";
    } else {
        try {
            if ($action === 'edit') {
                $id = (int)$_POST['course_id'];
                $courseModel->update($id, $code, $name, $description);
                $message = "Course updated successfully.";
                $messageType = "success";
                $editMode = false;
                $editCourse = null;
            } else {
                // Check if code already exists
                $existing = $courseModel->findByCode($code);
                if ($existing) {
                    $message = "A course with course code '$code' already exists.";
                    $messageType = "danger";
                } else {
                    $courseModel->create($code, $name, $description);
                    $message = "Course '$name' ($code) created successfully.";
                    $messageType = "success";
                }
            }
        } catch (Exception $e) {
            $message = "Error saving course: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

$courses = $courseModel->all();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Courses</h1>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    <!-- Course Form -->
    <div class="card">
        <h2 class="card-title"><?= $editMode ? 'Edit Course' : 'Add New Course' ?></h2>
        <form method="POST" action="courses.php">
            <input type="hidden" name="action" value="<?= $editMode ? 'edit' : 'add' ?>">
            <?php if ($editMode): ?>
                <input type="hidden" name="course_id" value="<?= htmlspecialchars($editCourse['course_id']) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="course_code">Course Code *</label>
                <input type="text" id="course_code" name="course_code" class="form-control" 
                       placeholder="e.g. CS101, WEB-201" 
                       value="<?= htmlspecialchars($editCourse['course_code'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="course_name">Course Name *</label>
                <input type="text" id="course_name" name="course_name" class="form-control" 
                       placeholder="e.g. Web Development Fundamentals" 
                       value="<?= htmlspecialchars($editCourse['course_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3" 
                          placeholder="Brief description of the course content"><?= htmlspecialchars($editCourse['description'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">
                    <?= $editMode ? 'Update Course' : 'Save Course' ?>
                </button>
                <?php if ($editMode): ?>
                    <a href="courses.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Courses List Table -->
    <div>
        <div class="card">
            <h2 class="card-title">Existing Courses (<?= count($courses) ?>)</h2>
            <?php if (empty($courses)): ?>
                <p>No courses found. Add your first course using the form.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Description</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['course_name']) ?></td>
                                    <td><?= htmlspecialchars($c['description'] ?? '—') ?></td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="courses.php?action=edit&id=<?= $c['course_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="courses.php?action=delete&id=<?= $c['course_id'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
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
