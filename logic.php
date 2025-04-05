<?php
require_once 'database.php';

class Logic {
    private $db;
    private $teacher_id = 1; // Simulated teacher ID
    private $homeroom_teacher_id = 1; // Simulated homeroom teacher ID
    private $is_dean = true; // Simulated dean status
    private $is_homeroom = true; // Simulated homeroom status

    public function __construct() {
        $this->db = new Database();
    }

    // Get role-based permissions
    public function getRoles() {
        return [
            'is_dean' => $this->is_dean,
            'is_homeroom' => $this->is_homeroom,
            'teacher_id' => $this->teacher_id,
            'homeroom_teacher_id' => $this->homeroom_teacher_id
        ];
    }

    // Fetch departments
    public function getDepartments() {
        return $this->db->conn->query("SELECT * FROM departments")->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch teachers
    public function getTeachers() {
        return $this->db->conn->query("SELECT * FROM teachers")->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch all courses
    public function getCourses() {
        return $this->db->conn->query("SELECT * FROM courses")->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch courses for a specific department
    public function getCoursesByDepartment($department_id) {
        $stmt = $this->db->conn->prepare("SELECT * FROM courses WHERE department_id = ?");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch courses for a teacher
    public function getTeacherCourses($teacher_id) {
        $stmt = $this->db->conn->prepare("SELECT * FROM courses WHERE teacher_id = ?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch students for a course
    public function getStudentsByCourse($course_id) {
        $stmt = $this->db->conn->prepare("SELECT s.*, sc.mark, sc.is_locked 
                                          FROM students s 
                                          JOIN student_courses sc ON s.student_id = sc.student_id 
                                          WHERE sc.course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Register a student
    public function registerStudent($first_name, $last_name, $middle_name, $department_id, $academic_year, $semester, $course_ids) {
        if (count($course_ids) < 3) {
            return "Student must register for at least 3 courses.";
        }

        $stmt = $this->db->conn->prepare("INSERT INTO students (first_name, last_name, middle_name, department_id, academic_year, semester) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiii", $first_name, $last_name, $middle_name, $department_id, $academic_year, $semester);
        $stmt->execute();
        $student_id = $this->db->conn->insert_id;

        foreach ($course_ids as $course_id) {
            $stmt = $this->db->conn->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $student_id, $course_id);
            $stmt->execute();
        }

        return "Student registered successfully with ID: $student_id";
    }

    // Add a teacher
    public function addTeacher($first_name, $last_name, $middle_name, $department_id, $is_homeroom, $is_dean) {
        $stmt = $this->db->conn->prepare("INSERT INTO teachers (first_name, last_name, middle_name, department_id, is_homeroom, is_dean) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiii", $first_name, $last_name, $middle_name, $department_id, $is_homeroom, $is_dean);
        $stmt->execute();
        return "Teacher added successfully!";
    }

    // Add a course
    public function addCourse($course_name, $department_id) {
        $stmt = $this->db->conn->prepare("INSERT INTO courses (course_name, department_id) VALUES (?, ?)");
        $stmt->bind_param("si", $course_name, $department_id);
        $stmt->execute();
        return "Course added successfully!";
    }

    // Assign a course to a teacher
    public function assignCourse($course_name, $department_id, $teacher_id) {
        $stmt = $this->db->conn->prepare("SELECT course_id FROM courses WHERE course_name = ? AND department_id = ?");
        $stmt->bind_param("si", $course_name, $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $course = $result->fetch_assoc();
            $course_id = $course['course_id'];

            $stmt = $this->db->conn->prepare("UPDATE courses SET teacher_id = ? WHERE course_id = ?");
            $stmt->bind_param("ii", $teacher_id, $course_id);
            $stmt->execute();
            return "Course assigned to teacher successfully!";
        }
        return "Course not found!";
    }

    // Upload marks for a course
    public function uploadMarks($course_id, $marks_file) {
        $stmt = $this->db->conn->prepare("SELECT student_id FROM student_courses WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $registered_students = [];
        while ($row = $result->fetch_assoc()) {
            $registered_students[] = $row['student_id'];
        }

        $errors = [];
        if (($handle = fopen($marks_file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $student_id = (int)$data[0];
                $mark = (float)$data[1];

                if (!in_array($student_id, $registered_students)) {
                    $errors[] = "Student ID $student_id is not registered in this course!";
                    continue;
                }

                $stmt = $this->db->conn->prepare("UPDATE student_courses SET mark = ? WHERE student_id = ? AND course_id = ?");
                $stmt->bind_param("dii", $mark, $student_id, $course_id);
                $stmt->execute();
            }
            fclose($handle);
        }

        if (empty($errors)) {
            return "Marks uploaded successfully!";
        }
        return implode("\n", $errors);
    }

    // Lock marks for a course
    public function lockMarks($course_id) {
        $stmt = $this->db->conn->prepare("UPDATE student_courses SET is_locked = 1 WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        return "Marks locked successfully!";
    }

    // Fetch homeroom data with dynamic courses
    public function getHomeroomData($homeroom_teacher_id, $academic_year, $semester) {
        // Step 1: Fetch the homeroom teacher's department
        $stmt = $this->db->conn->prepare("SELECT department_id FROM teachers WHERE teacher_id = ? AND is_homeroom = 1");
        $stmt->bind_param("i", $homeroom_teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 0) {
            return ['students' => [], 'courses' => []];
        }
        $teacher = $result->fetch_assoc();
        $department_id = $teacher['department_id'];

        // Step 2: Fetch all courses in the teacher's department
        $courses = $this->getCoursesByDepartment($department_id);

        // Step 3: Fetch students and their registered courses
        $stmt = $this->db->conn->prepare("SELECT s.student_id, s.first_name, s.last_name 
                                          FROM students s 
                                          JOIN student_courses sc ON s.student_id = sc.student_id 
                                          JOIN courses c ON sc.course_id = c.course_id 
                                          WHERE c.department_id = ? 
                                          AND s.academic_year = ? AND s.semester = ? 
                                          GROUP BY s.student_id");
        $stmt->bind_param("iii", $department_id, $academic_year, $semester);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Step 4: Fetch marks for each student and check if all marks are uploaded
        foreach ($students as &$student) {
            $student_id = $student['student_id'];
            $marks = [];
            $all_marks_uploaded = true;
            $total = 0;
            $count = 0;

            // Fetch the courses the student is registered in
            $stmt = $this->db->conn->prepare("SELECT c.course_id, c.course_name, sc.mark 
                                              FROM student_courses sc 
                                              JOIN courses c ON sc.course_id = c.course_id 
                                              WHERE sc.student_id = ? AND c.department_id = ?");
            $stmt->bind_param("ii", $student_id, $department_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $registered_courses = $result->fetch_all(MYSQLI_ASSOC);

            // Map marks to course IDs
            $mark_map = [];
            foreach ($registered_courses as $course) {
                $mark_map[$course['course_id']] = $course['mark'];
            }

            // Populate marks for all courses in the department
            foreach ($courses as $course) {
                $course_id = $course['course_id'];
                if (isset($mark_map[$course_id])) {
                    $mark = $mark_map[$course_id];
                    $marks[$course_id] = $mark !== null ? $mark : '-';
                    if ($mark !== null) {
                        $total += $mark;
                        $count++;
                    } else {
                        $all_marks_uploaded = false;
                    }
                } else {
                    $marks[$course_id] = '-'; // Not registered in this course
                }
            }

            $student['marks'] = $marks;
            $student['all_marks_uploaded'] = $all_marks_uploaded;

            // Calculate total, average, rank, and status only if all marks are uploaded
            if ($all_marks_uploaded && $count > 0) {
                $student['total'] = $total;
                $student['average'] = $total / $count;
            } else {
                $student['total'] = '-';
                $student['average'] = '-';
            }
        }

        // Step 5: Calculate rank (only for students with all marks uploaded)
        $students_with_marks = array_filter($students, function($student) {
            return $student['all_marks_uploaded'];
        });
        usort($students_with_marks, function($a, $b) {
            return ($b['average'] ?? 0) <=> ($a['average'] ?? 0);
        });

        $rank = 1;
        foreach ($students_with_marks as &$student) {
            $student['rank'] = $rank++;
            $student['status'] = $student['average'] >= 50 ? 'PASS' : 'FAIL';
        }

        // Assign ranks to the original student array
        foreach ($students as &$student) {
            if (!$student['all_marks_uploaded']) {
                $student['rank'] = '-';
                $student['status'] = '-';
            } else {
                foreach ($students_with_marks as $ranked_student) {
                    if ($ranked_student['student_id'] == $student['student_id']) {
                        $student['rank'] = $ranked_student['rank'];
                        $student['status'] = $ranked_student['status'];
                        break;
                    }
                }
            }
        }

        return [
            'students' => $students,
            'courses' => $courses
        ];
    }
}
?>