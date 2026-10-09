<?php
// admin/reports.php -- Summary reports
require_once __DIR__ . '/../config/db.php';

// Summative reporting queries using PDO
$totalCourses = (int)$db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalClasses = (int)$db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$totalStudents = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeEnrollments = (int)$db->query("SELECT COUNT(*) FROM enrollments WHERE status = 'active'")->fetchColumn();
$cancelledEnrollments = (int)$db->query("SELECT COUNT(*) FROM enrollments WHERE status = 'cancelled'")->fetchColumn();

// Class Capacity & Utilization Report (Multi-table JOIN with Aggregations)
$classReportSql = "
    SELECT c.class_id, c.class_code, c.schedule, c.instructor, c.slots AS remaining_slots,
           co.course_code, co.course_name,
           COUNT(CASE WHEN e.status = 'active' THEN 1 END) AS active_count,
           COUNT(CASE WHEN e.status = 'cancelled' THEN 1 END) AS cancelled_count,
           (c.slots + COUNT(CASE WHEN e.status = 'active' THEN 1 END)) AS calculated_capacity
    FROM classes c
    JOIN courses co ON c.course_id = co.course_id
    LEFT JOIN enrollments e ON c.class_id = e.class_id
    GROUP BY c.class_id, co.course_id
    ORDER BY co.course_code ASC, c.class_code ASC
";
$classReports = $db->query($classReportSql)->fetchAll();

// Course Popularity Summary
$courseReportSql = "
    SELECT co.course_id, co.course_code, co.course_name,
           COUNT(DISTINCT c.class_id) AS total_classes,
           COUNT(CASE WHEN e.status = 'active' THEN 1 END) AS total_active_enrolled
    FROM courses co
    LEFT JOIN classes c ON co.course_id = c.course_id
    LEFT JOIN enrollments e ON c.class_id = e.class_id
    GROUP BY co.course_id
    ORDER BY total_active_enrolled DESC, co.course_name ASC
";
$courseReports = $db->query($courseReportSql)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Reports</h1>
</div>

<!-- Stat Cards -->
<div class="grid-3" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-num"><?= $totalCourses ?></div>
        <div class="stat-label">Active Courses</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $totalClasses ?></div>
        <div class="stat-label">Class Sections</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $totalStudents ?></div>
        <div class="stat-label">Registered Students</div>
    </div>
    <div class="stat-card">
        <div class="stat-num" style="color: var(--success);"><?= $activeEnrollments ?></div>
        <div class="stat-label">Active Enrollments</div>
    </div>
    <div class="stat-card">
        <div class="stat-num" style="color: var(--danger);"><?= $cancelledEnrollments ?></div>
        <div class="stat-label">Cancelled Enrollments</div>
    </div>
    <div class="stat-card">
        <div class="stat-num" style="color: var(--warning);"><?= $activeEnrollments + $cancelledEnrollments ?></div>
        <div class="stat-label">Total Transactions</div>
    </div>
</div>

<!-- Class Capacity Breakdown -->
<div class="card">
    <h2 class="card-title">Class Capacity &amp; Slot Utilization</h2>
    <?php if (empty($classReports)): ?>
        <p>No class data to report.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Class Section</th>
                        <th>Course</th>
                        <th>Instructor</th>
                        <th>Schedule</th>
                        <th style="text-align: center;">Active Enrolled</th>
                        <th style="text-align: center;">Cancelled</th>
                        <th style="text-align: center;">Remaining Slots</th>
                        <th style="text-align: center;">Total Capacity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classReports as $cr): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cr['class_code']) ?></strong></td>
                            <td><?= htmlspecialchars($cr['course_name']) ?> (<?= htmlspecialchars($cr['course_code']) ?>)</td>
                            <td><?= htmlspecialchars($cr['instructor']) ?></td>
                            <td><?= htmlspecialchars($cr['schedule']) ?></td>
                            <td style="text-align: center; font-weight: 600; color: var(--success);"><?= $cr['active_count'] ?></td>
                            <td style="text-align: center; color: var(--danger);"><?= $cr['cancelled_count'] ?></td>
                            <td style="text-align: center; font-weight: 700;"><?= $cr['remaining_slots'] ?></td>
                            <td style="text-align: center;"><?= $cr['calculated_capacity'] ?></td>
                            <td>
                                <?php if ((int)$cr['remaining_slots'] === 0): ?>
                                    <span class="badge badge-slot-full">Class Full</span>
                                <?php else: ?>
                                    <span class="badge badge-slot-ok">Open (<?= $cr['remaining_slots'] ?> left)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Course Breakdown -->
<div class="card">
    <h2 class="card-title">Course Enrollment Breakdown</h2>
    <?php if (empty($courseReports)): ?>
        <p>No course data available.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th style="text-align: center;">Class Sections Offered</th>
                        <th style="text-align: center;">Total Active Students</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courseReports as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
                            <td><?= htmlspecialchars($c['course_name']) ?></td>
                            <td style="text-align: center;"><?= $c['total_classes'] ?></td>
                            <td style="text-align: center; font-weight: 700; color: var(--primary);"><?= $c['total_active_enrolled'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
