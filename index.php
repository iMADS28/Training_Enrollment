<?php
// index.php -- Landing page
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Quick stats for the dashboard
$courseCount = (int)$db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$classCount = (int)$db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$studentCount = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$enrollmentCount = (int)$db->query("SELECT COUNT(*) FROM enrollments WHERE status = 'active'")->fetchColumn();
?>

<div class="page-header">
    <h1>Training Enrollment System</h1>
</div>

<!-- Dashboard Metrics -->
<div class="grid-3" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-num"><?= $courseCount ?></div>
        <div class="stat-label">Courses</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $classCount ?></div>
        <div class="stat-label">Class Sections</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $studentCount ?></div>
        <div class="stat-label">Registered Students</div>
    </div>
</div>

<!-- Quick Action Navigation Cards -->
<div class="section-title">Modules</div>
<div class="grid-2">
    <div class="card">
        <h3 class="card-title">Course Catalog</h3>
        <p>Register, update, and manage training course offerings with course codes and descriptions.</p>
        <a href="admin/courses.php" class="btn btn-primary">Manage Courses &rarr;</a>
    </div>

    <div class="card">
        <h3 class="card-title">Class Schedules &amp; Capacities</h3>
        <p>Create and schedule specific class sections, assign instructors, and set seat quotas.</p>
        <a href="admin/classes.php" class="btn btn-primary">Manage Classes &rarr;</a>
    </div>

    <div class="card">
        <h3 class="card-title">Record Student &amp; Enroll</h3>
        <p>Record a new student and enroll them into an open class section.</p>
        <a href="admin/students.php" class="btn btn-primary">Record Student &rarr;</a>
    </div>

    <div class="card">
        <h3 class="card-title">Enroll Existing Student</h3>
        <p>Enroll an already registered student into an additional class.</p>
        <a href="admin/enroll.php" class="btn btn-secondary">Enroll Student &rarr;</a>
    </div>

    <div class="card">
        <h3 class="card-title">Enrollments Management</h3>
        <p>View all enrollments with full details and cancel active enrollments with slot restoration.</p>
        <a href="admin/enrollments.php" class="btn btn-secondary">View Enrollments &rarr;</a>
    </div>

    <div class="card">
        <h3 class="card-title">Reports</h3>
        <p>Analyze class capacity utilization, active vs cancelled statistics, and course breakdown.</p>
        <a href="admin/reports.php" class="btn btn-secondary">View Reports &rarr;</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
