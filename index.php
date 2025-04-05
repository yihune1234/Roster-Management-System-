<?php
require_once 'logic.php';
$logic = new Logic();

// Fetch initial data
$roles = $logic->getRoles();
$departments = $logic->getDepartments();
$teachers = $logic->getTeachers();
$courses = $logic->getCourses();
$default_department_id = 1; // Computer Science
$allowed_courses = $logic->getCoursesByDepartment($default_department_id);
$teacher_courses = $logic->getTeacherCourses($roles['teacher_id']);

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Register Student
    if (isset($_POST['register_student'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $middle_name = $_POST['middle_name'] ?? null;
        $department_id = (int)$_POST['department_id'];
        $academic_year = (int)$_POST['academic_year'];
        $semester = (int)$_POST['semester'];
        $course_ids = $_POST['course_ids'] ?? [];

        $message = $logic->registerStudent($first_name, $last_name, $middle_name, $department_id, $academic_year, $semester, $course_ids);
    }

    // Add Teacher
    if (isset($_POST['add_teacher'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $middle_name = $_POST['middle_name'] ?? null;
        $department_id = (int)$_POST['department_id'];
        $is_homeroom = isset($_POST['is_homeroom']) ? 1 : 0;
        $is_dean = isset($_POST['is_dean']) ? 1 : 0;

        $message = $logic->addTeacher($first_name, $last_name, $middle_name, $department_id, $is_homeroom, $is_dean);
        header("Location: index.php");
        exit;
    }

    // Add Course
    if (isset($_POST['add_course'])) {
        $course_name = $_POST['course_name'];
        $department_id = (int)$_POST['department_id'];

        $message = $logic->addCourse($course_name, $department_id);
        header("Location: index.php");
        exit;
    }

    // Assign Course to Teacher
    if (isset($_POST['assign_course'])) {
        $course_name = $_POST['course_name'];
        $department_id = (int)$_POST['department_id'];
        $teacher_id = (int)$_POST['teacher_id'];

        $message = $logic->assignCourse($course_name, $department_id, $teacher_id);
        header("Location: index.php");
        exit;
    }

    // Upload Marks
    if (isset($_POST['upload_marks'])) {
        $course_id = (int)$_POST['course_id'];
        $marks_file = $_FILES['marks_file']['tmp_name'];

        $message = $logic->uploadMarks($course_id, $marks_file);
    }

    // Lock Marks
    if (isset($_POST['lock_marks'])) {
        $course_id = (int)$_POST['course_id'];
        $message = $logic->lockMarks($course_id);
    }
}

// Fetch students for a selected course (Teacher Dashboard)
$selected_course_id = isset($_POST['teacher_course']) ? (int)$_POST['teacher_course'] : 0;
$students = $selected_course_id ? $logic->getStudentsByCourse($selected_course_id) : [];

// Fetch homeroom data
$homeroom_data = ['students' => [], 'courses' => []];
if (isset($_POST['load_homeroom'])) {
    $academic_year = (int)$_POST['academic_year'];
    $semester = (int)$_POST['semester'];
    $homeroom_data = $logic->getHomeroomData($roles['homeroom_teacher_id'], $academic_year, $semester);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Haramaya University - Student</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1 style="text-align:center" >Haramaya University - Student Roster Management</h1>

        <?php if ($message): ?>
            <script>alert("<?php echo addslashes($message); ?>");</script>
        <?php endif; ?>

        <!-- Tab Bar -->
        <div class="tab-bar">
            <a href="#student-registration" class="tab-link active" onclick="showTab('student-registration-section')">Student Registration</a>
            <?php if ($roles['is_dean']): ?>
                <a href="#dean" class="tab-link" onclick="showTab('dean-section')">Dean Dashboard</a>
            <?php endif; ?>
            <a href="#teacher" class="tab-link" onclick="showTab('dashboard-section')">Teacher Dashboard</a>
            <?php if ($roles['is_homeroom']): ?>
                <a href="#homeroom" class="tab-link" onclick="showTab('homeroom-section')">Homeroom Roster</a>
            <?php endif; ?>
        </div>

        <!-- Student Registration Section -->
        <div id="student-registration-section" class="tab-content active">
            <h2>Student Registration</h2>
            <form method="post">
                <label>First Name:</label>
                <input type="text" name="first_name" required>
                <label>Last Name:</label>
                <input type="text" name="last_name" required>
                <label>Middle Name:</label>
                <input type="text" name="middle_name">
                <label>Department:</label>
                <select name="department_id" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Academic Year:</label>
                <select name="academic_year" required>
                    <option value="">Select Year</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                <label>Semester:</label>
                <select name="semester" required>
                    <option value="">Select Semester</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                </select>
                <label>Select Courses (minimum 3):</label>
                <div>
                    <?php foreach ($allowed_courses as $course): ?>
                        <div>
                            <input type="checkbox" name="course_ids[]" value="<?php echo $course['course_id']; ?>">
                            <label><?php echo $course['course_name']; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="register_student">Register Student</button>
            </form>
            <p><strong>Note:</strong> Courses are preloaded for the default department (Computer Science). For other departments, courses will be added by the dean.</p>
        </div>

        <!-- Dean Section -->
        <?php if ($roles['is_dean']): ?>
        <div id="dean-section" class="tab-content">
            <h2>Dean Dashboard</h2>
            <div>
                <h3>Add Teacher</h3>
                <form method="post">
                    <label>First Name:</label>
                    <input type="text" name="first_name" required>
                    <label>Last Name:</label>
                    <input type="text" name="last_name" required>
                    <label>Middle Name:</label>
                    <input type="text" name="middle_name">
                    <label>Department:</label>
                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Is Homeroom Teacher:</label>
                    <input type="checkbox" name="is_homeroom" value="1">
                    <label>Is Dean:</label>
                    <input type="checkbox" name="is_dean" value="1">
                    <button type="submit" name="add_teacher">Add Teacher</button>
                </form>
            </div>

            <div>
                <h3>Add New Course</h3>
                <form method="post">
                    <label>Course Name:</label>
                    <input type="text" name="course_name" required>
                    <label>Department:</label>
                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="add_course">Add Course</button>
                </form>
            </div>

            <div>
                <h3>Assign Course to Teacher</h3>
                <form method="post">
                    <label>Course Name:</label>
                    <select name="course_name" required>
                        <option value="">Select Course</option>
                        <?php
                        $course_options = '';
                        foreach ($courses as $course) {
                            $course_options .= "<option value=\"{$course['course_id']}\">{$course['course_name']}</option>";
                        }
                        echo $course_options;
                        ?>
                    </select>
                    <label>Department:</label>
                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Teacher:</label>
                    <select name="teacher_id" required>
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['teacher_id']; ?>"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign_course">Assign Course</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Teacher Dashboard Section -->
        <div id="dashboard-section" class="tab-content">
            <h2>Teacher Dashboard</h2>
            <form method="post">
                <label>Select Course:</label>
                <select name="teacher_course">
                    <option value="">Select a Course</option>
                    <?php foreach ($teacher_courses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo $selected_course_id == $course['course_id'] ? 'selected' : ''; ?>>
                            <?php echo $course['course_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Load Students</button>
            </form>

            <?php if ($selected_course_id): ?>
                <div>
                    <h3>Upload Marks (CSV)</h3>
                    <p>CSV Format: student_id,mark (e.g., 1,85)</p>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                        <input type="file" name="marks_file" accept=".csv" required>
                        <button type="submit" name="upload_marks">Upload Marks</button>
                    </form>
                </div>

                <div>
                    <h3>Students and Marks</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Middle Name</th>
                                <th>Mark</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo $student['student_id']; ?></td>
                                    <td><?php echo $student['first_name']; ?></td>
                                    <td><?php echo $student['last_name']; ?></td>
                                    <td><?php echo $student['middle_name'] ?? ''; ?></td>
                                    <td><?php echo $student['mark'] ?? '-'; ?></td>
                                    <td><?php echo $student['is_locked'] ? 'Locked' : 'Not Locked'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <form method="post">
                        <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                        <button type="submit" name="lock_marks">Lock Marks</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Homeroom Section -->
        <?php if ($roles['is_homeroom']): ?>
        <div id="homeroom-section" class="tab-content">
            <h2>Homeroom Roster (For Homeroom Teachers)</h2>
            <form method="post">
                <label>Academic Year:</label>
                <select name="academic_year">
                    <option value="">Select Year</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                <label>Semester:</label>
                <select name="semester">
                    <option value="">Select Semester</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                </select>
                <button type="submit" name="load_homeroom">Load Data</button>
            </form>

            <?php if (!empty($homeroom_data['students'])): ?>
                <div>
                    <h3>Homeroom Roster</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>ID</th>
                                <?php foreach ($homeroom_data['courses'] as $course): ?>
                                    <th><?php echo htmlspecialchars($course['course_name']); ?></th>
                                <?php endforeach; ?>
                                <th>Total</th>
                                <th>Average</th>
                                <th>Rank</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($homeroom_data['students'] as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td>ABC<?php echo $student['student_id']; ?>/<?php echo $_POST['academic_year']; ?></td>
                                    <?php foreach ($homeroom_data['courses'] as $course): ?>
                                        <td><?php echo isset($student['marks'][$course['course_id']]) ? htmlspecialchars($student['marks'][$course['course_id']]) : '-'; ?></td>
                                    <?php endforeach; ?>
                                    <td><?php echo htmlspecialchars($student['total']); ?></td>
                                    <td><?php echo htmlspecialchars($student['average']); ?></td>
                                    <td><?php echo htmlspecialchars($student['rank']); ?></td>
                                    <td class="<?php echo $student['status'] == 'PASS' ? 'pass' : 'fail'; ?>">
                                        <?php echo htmlspecialchars($student['status']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="scripts.js"></script>
</body>
</html>