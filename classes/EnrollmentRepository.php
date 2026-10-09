<?php
// classes/EnrollmentRepository.php
// Data-access class that records students, enrollments and slot updates
// inside a single database transaction.

class EnrollmentRepository {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Record a NEW student AND enroll them into a class inside ONE transaction.
     * 1. beginTransaction()
     * 2. Check that the class still has available slots
     * 3. INSERT INTO students (full_name, email, phone)
     * 4. $student_id = lastInsertId()
     * 5. INSERT INTO enrollments (student_id, class_id)
     * 6. UPDATE classes SET slots = slots - 1
     * 7. commit(); otherwise rollBack()
     * 
     * @throws Exception when class not found, no slots left, or on query error
     * @return int The newly created enrollment ID
     */
    public function recordStudent($full_name, $email, $phone, $class_id) {
        try {
            $this->db->beginTransaction();

            // Check class existence and available slots with row lock
            $stmt = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :class_id FOR UPDATE");
            $stmt->execute([':class_id' => $class_id]);
            $class = $stmt->fetch();

            if (!$class) {
                $this->db->rollBack();
                throw new Exception("The selected class section does not exist.");
            }

            if ((int)$class['slots'] <= 0) {
                $this->db->rollBack();
                throw new Exception("No slots available in the selected class.");
            }

            // Insert new student
            $stmt = $this->db->prepare(
                "INSERT INTO students (full_name, email, phone) VALUES (:full_name, :email, :phone)"
            );
            $stmt->execute([
                ':full_name' => $full_name,
                ':email'     => $email,
                ':phone'     => $phone
            ]);
            $student_id = (int)$this->db->lastInsertId();

            // Insert enrollment
            $stmt = $this->db->prepare(
                "INSERT INTO enrollments (student_id, class_id, status) VALUES (:student_id, :class_id, 'active')"
            );
            $stmt->execute([
                ':student_id' => $student_id,
                ':class_id'   => $class_id
            ]);
            $enrollment_id = (int)$this->db->lastInsertId();

            // Decrement class slots
            $stmt = $this->db->prepare(
                "UPDATE classes SET slots = slots - 1 WHERE class_id = :class_id"
            );
            $stmt->execute([':class_id' => $class_id]);

            $this->db->commit();
            return $enrollment_id;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Enroll an EXISTING student into a class inside ONE transaction.
     * 1. Check existing active enrollment (prevent duplicate)
     * 2. Check class still has available slots
     * 3. INSERT INTO enrollments (student_id, class_id)
     * 4. UPDATE classes SET slots = slots - 1
     * 
     * @throws Exception when validation fails or no slots left
     * @return int The newly created enrollment ID
     */
    public function enroll($student_id, $class_id) {
        try {
            $this->db->beginTransaction();

            // Verify student exists
            $stmt = $this->db->prepare("SELECT student_id FROM students WHERE student_id = :student_id");
            $stmt->execute([':student_id' => $student_id]);
            if (!$stmt->fetch()) {
                $this->db->rollBack();
                throw new Exception("Student record not found.");
            }

            // Prevent duplicate active enrollment in the same class
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM enrollments WHERE student_id = :student_id AND class_id = :class_id AND status = 'active'"
            );
            $stmt->execute([
                ':student_id' => $student_id,
                ':class_id'   => $class_id
            ]);
            if ($stmt->fetchColumn() > 0) {
                $this->db->rollBack();
                throw new Exception("Student is already actively enrolled in this class.");
            }

            // Check class existence and available slots with row lock
            $stmt = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :class_id FOR UPDATE");
            $stmt->execute([':class_id' => $class_id]);
            $class = $stmt->fetch();

            if (!$class) {
                $this->db->rollBack();
                throw new Exception("The selected class section does not exist.");
            }

            if ((int)$class['slots'] <= 0) {
                $this->db->rollBack();
                throw new Exception("No slots available in the selected class.");
            }

            // Insert enrollment
            $stmt = $this->db->prepare(
                "INSERT INTO enrollments (student_id, class_id, status) VALUES (:student_id, :class_id, 'active')"
            );
            $stmt->execute([
                ':student_id' => $student_id,
                ':class_id'   => $class_id
            ]);
            $enrollment_id = (int)$this->db->lastInsertId();

            // Decrement class slots
            $stmt = $this->db->prepare(
                "UPDATE classes SET slots = slots - 1 WHERE class_id = :class_id"
            );
            $stmt->execute([':class_id' => $class_id]);

            $this->db->commit();
            return $enrollment_id;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancel an active enrollment inside ONE transaction:
     * 1. UPDATE enrollments SET status = 'cancelled'
     * 2. UPDATE classes SET slots = slots + 1
     * 
     * @throws Exception if enrollment not found or already cancelled
     * @return bool
     */
    public function cancel($enrollment_id) {
        try {
            $this->db->beginTransaction();

            // Fetch enrollment with lock
            $stmt = $this->db->prepare("SELECT * FROM enrollments WHERE enrollment_id = :id FOR UPDATE");
            $stmt->execute([':id' => $enrollment_id]);
            $enrollment = $stmt->fetch();

            if (!$enrollment) {
                $this->db->rollBack();
                throw new Exception("Enrollment record not found.");
            }

            if ($enrollment['status'] === 'cancelled') {
                $this->db->rollBack();
                throw new Exception("Enrollment is already cancelled.");
            }

            // Update status
            $stmt = $this->db->prepare("UPDATE enrollments SET status = 'cancelled' WHERE enrollment_id = :id");
            $stmt->execute([':id' => $enrollment_id]);

            // Restore slot
            $stmt = $this->db->prepare("UPDATE classes SET slots = slots + 1 WHERE class_id = :class_id");
            $stmt->execute([':class_id' => $enrollment['class_id']]);

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Fetch all enrollments joined with students, classes, and courses.
     * Supports search and status filtering.
     * 
     * @param string $search
     * @param string $status
     * @return array
     */
    public function allWithDetails($search = '', $status = '') {
        $sql = "SELECT e.enrollment_id, e.enrollment_date, e.status,
                       s.student_id, s.full_name, s.email, s.phone,
                       c.class_id, c.class_code, c.schedule, c.instructor, c.slots,
                       co.course_id, co.course_code, co.course_name
                FROM enrollments e
                JOIN students s ON e.student_id = s.student_id
                JOIN classes c ON e.class_id = c.class_id
                JOIN courses co ON c.course_id = co.course_id";

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(s.full_name LIKE :search OR s.email LIKE :search OR c.class_code LIKE :search OR co.course_name LIKE :search OR co.course_code LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if (!empty($status) && in_array($status, ['active', 'cancelled'])) {
            $where[] = "e.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY e.enrollment_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch single enrollment with joined details.
     */
    public function findWithDetails($enrollment_id) {
        $sql = "SELECT e.enrollment_id, e.enrollment_date, e.status,
                       s.student_id, s.full_name, s.email, s.phone,
                       c.class_id, c.class_code, c.schedule, c.instructor, c.slots,
                       co.course_id, co.course_code, co.course_name
                FROM enrollments e
                JOIN students s ON e.student_id = s.student_id
                JOIN classes c ON e.class_id = c.class_id
                JOIN courses co ON c.course_id = co.course_id
                WHERE e.enrollment_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $enrollment_id]);
        return $stmt->fetch();
    }
}
