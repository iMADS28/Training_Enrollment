<?php
// includes/header.php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (preg_match('#^(.*?/training_enrollment)/#i', $scriptName, $matches)) {
    $baseUrl = $matches[1] . '/';
} else {
    $dir = dirname($scriptName);
    if (basename($dir) === 'admin') {
        $baseUrl = dirname($dir) . '/';
    } else {
        $baseUrl = rtrim($dir, '/') . '/';
    }
}
$baseUrl = str_replace('//', '/', $baseUrl);
if ($baseUrl === '/') {
    $baseUrl = '/training_enrollment/';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Enrollment System</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>css/style.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-top">
            <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="brand-title">Training Enrollment</a>
        </div>
        <nav class="sidebar-links">
            <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="<?= str_ends_with($scriptName, 'index.php') || str_ends_with($scriptName, 'home.php') ? 'active' : '' ?>">Home</a>
            <a href="<?= htmlspecialchars($baseUrl) ?>admin/courses.php" class="<?= str_ends_with($scriptName, 'courses.php') ? 'active' : '' ?>">Courses</a>
            <a href="<?= htmlspecialchars($baseUrl) ?>admin/classes.php" class="<?= str_ends_with($scriptName, 'classes.php') ? 'active' : '' ?>">Classes</a>
            <a href="<?= htmlspecialchars($baseUrl) ?>admin/students.php" class="<?= str_ends_with($scriptName, 'students.php') ? 'active' : '' ?>">Record Student</a>
            <a href="<?= htmlspecialchars($baseUrl) ?>admin/enroll.php" class="<?= str_ends_with($scriptName, 'enroll.php') ? 'active' : '' ?>">Enroll Student</a>
            <a href="<?= htmlspecialchars($baseUrl) ?>admin/enrollments.php" class="<?= str_ends_with($scriptName, 'enrollments.php') ? 'active' : '' ?>">Enrollments</a>
            <a href="<?= htmlspecialchars($baseUrl) ?>admin/reports.php" class="<?= str_ends_with($scriptName, 'reports.php') ? 'active' : '' ?>">Reports</a>
        </nav>
    </aside>

    <div class="content-wrapper">
        <main class="main-body">
