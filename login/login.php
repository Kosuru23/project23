<?php
session_start();

require_once '../databases/connect.php';
require_once '../functions/functions.php';
require_once '../databases/database.class.php';
require_once '../databases/faculty.classes.php';
include '../functions/google_fonts.php';

$username = $password = '';
$db = new Database();
$user = new user($db);
$faculty = new faculty($db);
$loginErr = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input(($_POST['email']));
    $password = clean_input($_POST['password']);

    if ($user->login($username, $password)) {
        $data = $user->fetch($username);
        $_SESSION['account'] = $data;
        switch ($data['user_type']) {
            case 'Student':
                $user->get_stud($username);
                header('Location: ../student-view/test.php');
                break;
            case 'Adviser':
                $faculty->get_adviser($username);
                header('Location: ../adviser-view/adviser.php');
                break;
            case 'Professor':
                $faculty->get_prof($username);
                header('Location: ../professor-view/professors.php');
                break;
            case 'Guidance':
                $faculty->get_guidance($username);
                header('Location: ../guidance-view/faculty.php');
                break;
            default:
                $loginErr = 'Invalid user type.';
                break;
        }
        exit;
    } else {
        $loginErr = 'Invalid username/password';
    }
} else {
    if (isset($_SESSION['account'])) {
        switch ($_SESSION['account']['user_type']) {
            case 'Student':
                $user->get_stud($username);
                header('location: ../student-view/test.php');
                break;
            case 'Adviser':
                $faculty->get_adviser($username);
                header('Location: ../adviser-view/adviser.php');
                break;
            case 'Professor':
                $faculty->get_prof($username);
                header('Location: ../professor-view/professors.php');
                break;
            case 'Guidance':
                $faculty->get_guidance($username);
                header('Location: ../guidance-view/faculty.php');
                break;
            default:
                $loginErr = 'Invalid user type.';
                break;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>Login</title>
    <link rel="stylesheet" href="loginStyle.css">
</head>

<body>
    <div class="container">
        <div class="logo">
          <img src="" alt="WMSU Logo" class="cat">
        </div>
        <h1>Nexuse.</h1>        
        <?php if (!empty($_SESSION['error_message'])): ?>
            <p style="color:red;"><?= $_SESSION['error_message'] ?></p>
            <?php $_SESSION['error_message'] = ""; ?>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="text" id="email" name="email">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password">
            </div>
            <button type="submit" id="continue_button" name="login">Continue</button>
        </form>

        <div class="links">
            <a href="register.php" id="create_account">Create Account</a>
            <a href="forgot_password/forgotPassword.php" id="forgot_password">Forget Password</a>
        </div>
    </div>
</body>

</html>