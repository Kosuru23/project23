<?php
class user {

    public $email;
    public $password;
    private $pdo;

    function __construct($db) {
        $this->pdo = $db->connect();
    }

    function register($email, $password, $ln, $fn, $mn, $user_type, $department) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $checkUser = $this->pdo->prepare($sql);
        $checkUser->bindParam(":email", $email);
        $checkUser->execute();
        

        if ($checkUser->rowCount() == 0) {
            $sql = "INSERT INTO users (email, password, last_name, first_name, middle_name, created_at, user_type, department_id) VALUES (:email, :password, :last_name, :first_name, :middle_name, :created_at, :user_type, :department_id)";
            
            $query = $this->pdo->prepare($sql);
            $query->bindParam(":email", $email);  
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $query->bindParam(":password", $passwordHash); 
            $query->bindParam(":last_name", $ln);
            $query->bindParam(":first_name", $fn);
            $query->bindParam(":middle_name", $mn);
            $t = date("Y-m-d H:i:s");
            $query->bindParam(":created_at", $t);
            $query->bindParam(":user_type", $user_type);
            $query->bindParam(":department_id", $department);
            
            return $query->execute();

        } else {
            return false; 
        }
    }

    function login($username, $password)
    {
        $sql = "SELECT * FROM users WHERE email = :username LIMIT 1 ";
        $query = $this->pdo->prepare($sql);

        $query->bindParam(':username', $username);

        if ($query->execute()) {
            $data = $query->fetch(PDO::FETCH_ASSOC);
            if ($data && password_verify($password, $data['password'])) {
                return true;
            }
        }

        return false;
    }

    function get_stud($username) {
        $sql = "SELECT * FROM student 
        JOIN users ON student.user_id = users.ids
        JOIN program ON student.program_id = program.id
        WHERE email = :email
        LIMIT 1;";

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
                $_SESSION['name'] = $data['name'];
                $_SESSION['student_id'] = $data['student_id'];
                $_SESSION['department_id'] = $data['department_id'];

                return true; 
            }
        }
       
        return false;
    }


    function fetch($username)
    {
        $sql = "SELECT * FROM users WHERE email = :username LIMIT 1;";
        $query = $this->pdo->prepare($sql);

        $query->bindParam('username', $username);
        $data = null;
        if ($query->execute()) {
            $data = $query->fetch();
        }

        return $data;
    }

    // function get_section(){
    //     $sql = "SELECT * FROM sections";

    //     $query = $this->pdo->prepare($sql);

    //     $data = null;

    //     if($query->execute()) {
    //         $data = $query->fetchAll();
    //     }
    //     return $data;
    // }
    
    function get_course($department_id) {
        $sql = "SELECT subject.id AS id, subject.name AS name, acronym FROM subject 
        LEFT JOIN department ON subject.department_id = department.id
        WHERE department.id = :department_id";

        $query = $this->pdo->prepare($sql);

        $query->bindParam(':department_id', $department_id);

        $data = null;

        if($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }

    function get_reasons(){
        $sql = "SELECT * FROM reason";

        $query = $this->pdo->prepare($sql);

        $data = null;

        if($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }

    function excuse($date_absent, $comment, $excuse_letter, $course_id, $student_id, $reason_id, $prof_id) {
        $sql = "INSERT INTO excuse_letter (excuse_letter, comment, date_submitted, date_absent, subject_id, student_id, reason_id, prof_id) VALUES 
        (:excuse_letter, :comment, :date_submitted, :date_absent, :subject_id, :student_id, :reason_id, :prof_id)";
        
        $query = $this->pdo->prepare($sql);

        $query->bindParam(":excuse_letter", $excuse_letter, PDO::PARAM_LOB);  
        $query->bindParam(":comment", $comment); 
        $query->bindParam(":date_absent", $date_absent);
        $t = date("Y-m-d");
        $query->bindParam(":date_submitted", $t);
        $query->bindParam(":subject_id", $course_id);
        $query->bindParam(":student_id", $student_id);
        $query->bindParam(":reason_id", $reason_id);
        $query->bindParam(":prof_id", $prof_id);

        if ($query->execute()) {
            $last = $this->pdo->lastInsertId();
            return $last;
        }

        return false;
    }

    function approval($excuse_letter_id) {
        $sql = "INSERT INTO approval (excuse_letter_id) VALUES (:excuse_letter_id)";

        $query = $this->pdo->prepare($sql);

        $query->bindParam(":excuse_letter_id", $excuse_letter_id);

        return $query->execute();
    }

    function get_prof($department_id) {
        $sql = "SELECT *, professors.ID as id FROM users 
        JOIN department ON department.id = users.department_id
        JOIN professors ON professors.user_id = users.ids
        WHERE (department.id = :department_id) AND (user_type LIKE 'Professor')";

        $query = $this->pdo->prepare($sql);

        $query->bindParam(':department_id', $department_id);

        $data = null;

        if($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }

    // function get_specific_prof($id) {
    //     $sql = "SELECT users.ids FROM excuse_letter
    //     LEFT JOIN professors ON excuse_letter.prof_id = professors.ID
    //     LEFT JOIN users ON professors.user_id = users.ids
    //     WHERE (excuse_letter.user_id = :id);";

    //     $query = $this->pdo->prepare($sql);

    //     $query->bindParam(":id", $id);

    //     $data = null;

    //     if($query->execute()) {
    //         $data = $query->fetchAll();
    //     }
    //     return $data;
    // }

    function excuse_letters($id) {
        $sql = "SELECT DISTINCT CONCAT(last_name, ', ', first_name, IFNULL(CONCAT(' ', middle_name), '')) AS professors_name, acronym, date_absent, date_submitted, comment, type, excuse_letter, professors.ID as prof_id, reason.id as reason_id, prof_awknowledge, approval.approved_adviser AS approval_adviser, approval.approved_guidance AS approval_guidance
        FROM excuse_letter 
        LEFT JOIN subject ON excuse_letter.subject_id = subject.id 
        LEFT JOIN reason ON excuse_letter.reason_id = reason.id
        LEFT JOIN student ON excuse_letter.student_id = student.student_id
        LEFT JOIN professors ON excuse_letter.prof_id = professors.ID
        LEFT JOIN users ON professors.user_id = users.ids
        LEFT JOIN approval ON approval.excuse_letter_id = excuse_letter.id
        WHERE (student.user_id = :user_id) AND (professors.ID = excuse_letter.prof_id)";
        
        $query = $this->pdo->prepare($sql);

        $query->bindParam(":user_id", $id);
        
        $data = null;

        if($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }  

    function edit($excuse_letter_id, $date_absent, $comment, $prof_id, $reason_id, $excuse_letter) {
        $sql = 'UPDATE excuse_letter SET comment = :comment, prof_id = :prof_id, reason_id = :reason_id, date_submitted = :date_submitted, date_absent = :date_absent';

        if(!empty($excuse_letter)) {
                    $sql .= ', excuse_letter = :excuse_letter';
                }
        $sql .= " WHERE id = :id"; 
            

        $query = $this->pdo->prepare($sql);

        $query->bindParam(":comment", $comment);
        $query->bindParam(":prof_id", $prof_id);
        $query->bindParam(":reason_id", $reason_id);
        $t = date("Y-m-d");
        $query->bindParam(":date_submitted", $t);
        $query->bindParam(":date_absent", $date_absent);
        $query->bindParam(":id", $excuse_letter_id);

        if(!empty($excuse_letter)) {
            $query->bindParam(":excuse_letter", $excuse_letter, PDO::PARAM_LOB);
        }   

       return $query->execute();
    }
}
?>