<?php
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "school_db";
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        $this->conn->query("CREATE DATABASE IF NOT EXISTS $this->dbname");
        $this->conn->select_db($this->dbname);
        $this->createTables();
    }

    private function createTables() {
        $this->conn->query("CREATE TABLE IF NOT EXISTS departments (
            department_id INT AUTO_INCREMENT PRIMARY KEY,
            department_name VARCHAR(100) NOT NULL
        )");

        $this->conn->query("CREATE TABLE IF NOT EXISTS teachers (
            teacher_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50),
            department_id INT,
            is_homeroom TINYINT(1) DEFAULT 0,
            is_dean TINYINT(1) DEFAULT 0,
            FOREIGN KEY (department_id) REFERENCES departments(department_id)
        )");

        $this->conn->query("CREATE TABLE IF NOT EXISTS courses (
            course_id INT AUTO_INCREMENT PRIMARY KEY,
            course_name VARCHAR(100) UNIQUE NOT NULL,
            department_id INT,
            teacher_id INT,
            FOREIGN KEY (department_id) REFERENCES departments(department_id),
            FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
        )");

        $this->conn->query("CREATE TABLE IF NOT EXISTS students (
            student_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50),
            department_id INT,
            academic_year INT NOT NULL,
            semester INT NOT NULL,
            FOREIGN KEY (department_id) REFERENCES departments(department_id)
        )");

        $this->conn->query("CREATE TABLE IF NOT EXISTS student_courses (
            student_id INT,
            course_id INT,
            mark FLOAT DEFAULT NULL,
            is_locked TINYINT(1) DEFAULT 0,
            PRIMARY KEY (student_id, course_id),
            FOREIGN KEY (student_id) REFERENCES students(student_id),
            FOREIGN KEY (course_id) REFERENCES courses(course_id)
        )");

        $this->conn->query("INSERT IGNORE INTO departments (department_id, department_name) VALUES 
            (1, 'Computer Science'), 
            (2, 'Mathematics'), 
            (3, 'Science')");
    }
}
?>

