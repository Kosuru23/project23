<?php
class faculty {

    public $email;
    public $password;
    private $pdo;

    function __construct($db) {
        $this->pdo = $db->connect();
    }

    function get_adviser($username) {
        $sql = "SELECT * FROM adviser
        JOIN users ON adviser.user_id = users.ids
        WHERE email = :email
        LIMIT 1;";

        $query = $this->pdo->prepare($sql);

        $query->bindParam(':email', $username);
        
        if ($query->execute()) {
            $data = $query->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                session_start();
                $_SESSION['ids'] = $data['ids'];
                $_SESSION['adviser_id'] = $data['id'];
                $_SESSION['last_name'] = $data['last_name'];
                $_SESSION['first_name'] = $data['first_name'];
                $_SESSION['middle_name'] = $data['middle_name'];
                $_SESSION['department_id'] = $data['department_id'];
                $_SESSION['user_type'] = $data['user_type'];
                $_SESSION['year_level'] = $data['year_level'];
                $_SESSION['login'] = date("Y-m-d H:i:s");
                
                return true; 
            }
        }
        
        return false;
    }

    function get_guidance($username) {
        $sql = "SELECT * FROM guidance
        JOIN users ON guidance.user_id = users.ids
        WHERE email = :email
        LIMIT 1";

        $query = $this->pdo->prepare($sql);

        $query->bindParam(':email', $username);
        
        if ($query->execute()) {
            $data = $query->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                session_start();
                $_SESSION['ids'] = $data['ids'];
                $_SESSION['guidance_id'] = $data['guidance_id'];
                $_SESSION['last_name'] = $data['last_name'];
                $_SESSION['first_name'] = $data['first_name'];
                $_SESSION['middle_name'] = $data['middle_name'];
                $_SESSION['user_type'] = $data['user_type'];
                $_SESSION['login'] = date("Y-m-d H:i:s");

                return true; 
            }
        }
        
        return false;
    }
    
    function get_prof($username) {
        $sql = "SELECT * FROM professors
        JOIN users ON professors.user_id = users.ids
        WHERE email = :email
        LIMIT 1";

        $query = $this->pdo->prepare($sql);

        $query->bindParam(':email', $username);
        
        if ($query->execute()) {
            $data = $query->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                session_start();
                $_SESSION['ids'] = $data['ids'];
                $_SESSION['last_name'] = $data['last_name'];
                $_SESSION['first_name'] = $data['first_name'];
                $_SESSION['middle_name'] = $data['middle_name'];
                $_SESSION['department_id'] = $data['department_id'];
                $_SESSION['user_type'] = $data['user_type'];
                $_SESSION['ID'] = $data['ID'];
                $_SESSION['login'] = date("Y-m-d H:i:s");

                return true; 
            }
        }
       
        return false;
    }

    function get_type($id){
        $sql = "SELECT name FROM department
        LEFT JOIN users ON department.id = users.department_id 
        WHERE ids = :id";

        $query = $this->pdo->prepare($sql);

        $query->bindParam(":id", $id); 
        $data = null;

        if ($query->execute()) {
            $data = $query->fetchColumn();  
        }
        return $data;
    }

    function get_dept() {
        $sql = "SELECT department.name as Name, COALESCE(COUNT(DISTINCT approval.noted_adviser), 0) as Count
        FROM department
        LEFT JOIN program ON department.id = program.department_id
        LEFT JOIN student ON student.program_id = program.id
        LEFT JOIN excuse_letter ON student.student_id = excuse_letter.student_id
        LEFT JOIN approval ON approval.excuse_letter_id = excuse_letter.id AND approval.approved_adviser = 1
        LEFT JOIN adviser ON approval.noted_adviser = adviser.id
        GROUP BY department.id, Name ";

        $query = $this->pdo->prepare($sql);

        $data = null;

        if($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }

    function get_total_year($subject) {
        $sql = "SELECT program.year_level, COUNT(CASE WHEN approval.approved_adviser = 'Approved' THEN approval.id END) AS TOTAL 
        FROM program 
        LEFT JOIN department ON department.id = program.department_id 
        LEFT JOIN student ON student.program_id = program.id
        LEFT JOIN excuse_letter ON excuse_letter.student_id = student.student_id
        LEFT JOIN subject ON excuse_letter.subject_id = subject.id 
        LEFT JOIN approval ON excuse_letter.id = approval.excuse_letter_id
        WHERE (department.name LIKE :name) AND (approval.approved_guidance = 'Pending')
        GROUP BY program.year_level
        ORDER BY program.year_level ASC";

        $query = $this->pdo->prepare($sql);
        $query->bindParam(":name", $subject); 
        $data = null;

        if($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }

    function get_course($id, $prof_id) {
        $sql = "SELECT subject.name, acronym, COUNT(excuse_letter.id) AS total FROM subject 
        LEFT JOIN excuse_letter ON subject.id = excuse_letter.subject_id AND excuse_letter.prof_id = :prof_id
        LEFT JOIN department ON subject.department_id = department.id
        LEFT JOIN approval ON excuse_letter.id = approval.excuse_letter_id
        WHERE (subject.department_id = :id) AND (approval.approved_guidance = 'Approved') AND (excuse_letter.prof_awknowledge = 'Pending')
        GROUP BY subject.id, subject.name, acronym 
        ORDER BY total DESC";

        $query = $this->pdo->prepare($sql);
        $query->bindParam(":id", $id); 
        $query->bindParam(":prof_id", $prof_id); 
        $data = null;

        if($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }
    
    function get_program($department_id = null, $year_level = null) {
        $data = [];

        if($_SESSION['user_type'] === 'Adviser') {
            $sql = "SELECT name FROM program WHERE (department_id = :department_id) AND (year_level = :year_level)";

            $query = $this->pdo->prepare($sql);
            $query->bindParam(":department_id", $department_id); 
            $query->bindParam(":year_level", $year_level); 

            if($query->execute()) {
                $data = $query->fetchAll();
            }
        }

        if($_SESSION['user_type'] === 'Professor') {
            $sql = "SELECT * FROM program ORDER BY program.name ASC";

            $query = $this->pdo->prepare($sql);

            if ($query->execute()) {
                $data = $query->fetchAll();
            }
        }

        return $data;
    }
    
    function get_excuse_letters($subject, $prof_id, $search= null, $filter = null) {
        $sql = "SELECT DISTINCT CONCAT(last_name, ', ', first_name, IFNULL(CONCAT(' ', middle_name), '')) AS name, date_submitted, date_absent, comment, program.name as course, reason.type, excuse_letter, excuse_letter.id as id, excuse_letter.prof_awknowledge as approval_type, excuse_letter.approval_date as prof_approved_date
        FROM excuse_letter 
        LEFT JOIN subject ON excuse_letter.subject_id = subject.id
        LEFT JOIN student ON excuse_letter.student_id = student.student_id
        LEFT JOIN reason ON excuse_letter.reason_id = reason.id
        LEFT JOIN program ON student.program_id = program.id
        LEFT JOIN users ON student.user_id = users.ids
        LEFT JOIN approval ON excuse_letter.id = approval.excuse_letter_id
        WHERE (subject.name = :subject_name) AND (excuse_letter.prof_id = :prof_id) AND (approval.approved_guidance = 'Approved')";
        
        if(!empty($search)) {
            $sql .="AND (CONCAT(last_name, ', ', first_name, IFNULL(CONCAT(' ', middle_name), '')) LIKE :search) ";
        }

        if(!empty($filter)) {
            $sql .="AND (program.name LIKE :filter) ";
        }        

        $query = $this->pdo->prepare($sql);
        $query->bindParam(":subject_name", $subject); 
        $query->bindParam(":prof_id", $prof_id); 

        if (!empty($search)) {
            $search = "%$search%";
            $query->bindParam(":search", $search);
        }

        if (!empty($filter)) {
            $query->bindParam(":filter", $filter);
        }
        
        $data = null;

        if($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }

    function get_adviser_excuse_letter($department_id, $search = null, $filter = null){
        $sql = "SELECT DISTINCT excuse_letter.id AS id, CONCAT(stud_user.last_name, ', ', stud_user.first_name, IFNULL(CONCAT(' ', stud_user.middle_name), '')) AS student_name, program.name, CONCAT(prof_user.last_name, ', ', prof_user.first_name, IFNULL(CONCAT(' ', prof_user.middle_name), '')) AS professor_name, 
        date_absent, date_submitted, comment, reason.type, excuse_letter, subject.name AS subject_name, approval.approved_adviser as approval, approval.date_adviser_approved as date_approved, approval.id as approval_id
        FROM excuse_letter
        JOIN student ON excuse_letter.student_id = student.student_id
        JOIN subject ON excuse_letter.subject_id = subject.id
        JOIN program ON student.program_id = program.id
        JOIN reason ON excuse_letter.reason_id = reason.id
        JOIN users AS stud_user ON student.user_id = stud_user.ids
        JOIN professors ON excuse_letter.prof_id = professors.ID
        JOIN users AS prof_user ON professors.user_id = prof_user.ids
        JOIN adviser ON program.year_level = adviser.year_level
        JOIN department ON program.department_id = department.id
        JOIN approval ON approval.excuse_letter_id = excuse_letter.id
        WHERE (department.id = :department_id) AND (program.year_level = adviser.year_level) ";

        if(!empty($search)) {
            $sql .="AND (CONCAT(stud_user.last_name, ', ', stud_user.first_name, IFNULL(CONCAT(' ', stud_user.middle_name), '')) LIKE :search) ";
        }

        if(!empty($filter)) {
            $sql .="AND (program.name LIKE :filter) ";
        }

        $query = $this->pdo->prepare($sql);

        $query->bindParam(":department_id", $department_id); 

        if (!empty($search)) {
            $search = "%$search%";
            $query->bindParam(":search", $search);
        }

        if (!empty($filter)) {
            $query->bindParam(":filter", $filter);
        }
        
        $data = null;

        if($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }

    function get_guidance_excuse_letter($subject, $year){
        $sql = "SELECT DISTINCT excuse_letter.id AS id, CONCAT(stud_user.last_name, ', ', stud_user.first_name, IFNULL(CONCAT(' ', stud_user.middle_name), '')) AS student_name, program.name, CONCAT(prof_user.last_name, ', ', prof_user.first_name, IFNULL(CONCAT(' ', prof_user.middle_name), '')) AS professor_name, 
        date_absent, date_submitted, comment, reason.type, excuse_letter, subject.name AS subject_name, approval.approved_guidance as approval, approval.date_guidance_approved as date_approved, approval.id as approval_id
        FROM excuse_letter
        JOIN student ON excuse_letter.student_id = student.student_id
        JOIN subject ON excuse_letter.subject_id = subject.id
        JOIN program ON student.program_id = program.id
        JOIN reason ON excuse_letter.reason_id = reason.id
        JOIN users AS stud_user ON student.user_id = stud_user.ids
        JOIN professors ON excuse_letter.prof_id = professors.ID
        JOIN users AS prof_user ON professors.user_id = prof_user.ids
        JOIN department ON program.department_id = department.id
        JOIN approval ON approval.excuse_letter_id = excuse_letter.id
        WHERE (department.name = :department_name) AND (program.year_level = :year) AND (approval.approved_adviser = 'Approved')";

        $query = $this->pdo->prepare($sql);

        $query->bindParam(":department_name", $subject); 
        $query->bindParam(":year", $year); 
        $data = null;

        if($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }
}