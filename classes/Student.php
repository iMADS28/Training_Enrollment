<?php
// classes/Student.php

class Student {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function find($id) {
        // Return one student by id (prepared statement)
        $stmt = $this->db->prepare("SELECT * FROM students WHERE student_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function all() {
        // Return all students ordered by full_name
        $stmt = $this->db->query("SELECT * FROM students ORDER BY full_name ASC");
        return $stmt->fetchAll();
    }

    public function create($full_name, $email = null, $phone = null) {
        $stmt = $this->db->prepare("INSERT INTO students (full_name, email, phone) VALUES (:full_name, :email, :phone)");
        $stmt->execute([
            ':full_name' => $full_name,
            ':email' => $email,
            ':phone' => $phone
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $full_name, $email = null, $phone = null) {
        $stmt = $this->db->prepare("UPDATE students SET full_name = :full_name, email = :email, phone = :phone WHERE student_id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':full_name' => $full_name,
            ':email' => $email,
            ':phone' => $phone
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM students WHERE student_id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
