<?php
include '../functions/google_fonts.php';
session_start();  

if (!isset($_SESSION['message'])) {
    $_SESSION['message'] = "";
    $_SESSION['message_color'] = "red"; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $proof = $_FILES['proof']['name'] ?? '';

    if (empty($name) || empty($lname) || empty($email) || empty($proof)) {
        $_SESSION['message'] = "Please fill up all form fields.";
        $_SESSION['message_color'] = "red";
    } else {
        $_SESSION['message'] = "Account requested successfully.";
        $_SESSION['message_color'] = "green";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>Create Account</title>
    <link rel="stylesheet" href="create-accountStyle.css">
</head>

<?php 
includeGoogleFont([
    'Josefin Sans:ital,wght@0,100..700;1,100..700', 
    'Poppins:wght@400;600'
]); 
?>

<body>
    <div class="container">
    <button class="return" type="button" onclick="window.location.href='../login_file/login.php'" id="return-button"> 
       <i class="fa-solid fa-circle-chevron-left"></i>Return
    </button>
        <div class="logo">
            <img src="../images/Nexuse.svg" alt="WMSU Logo" class="cat">
        </div>
        <h1>Nexuse.</h1>      
        <?php if (!empty($_SESSION['message'])): ?>
            <p style="color: <?= $_SESSION['message_color'] ?>;"><?= $_SESSION['message'] ?></p>
            <?php $_SESSION['message'] = ""; // Clear the message ?>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <div class="form-row">
                    <div class="form-col">
                        <label for="name">First name:</label>
                        <input type="text" id="name" name="name">
                    </div>
                    <div class="form-col">
                        <label for="mname">Middle name (optional):</label>
                        <input type="text" id="mname" name="mname">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="form-row">
                    <div class="form-col">
                        <label for="lname">Last name:</label>
                        <input type="text" id="lname" name="lname">
                    </div>
                    <div class="form-col">
                        <label for="email">School Email:</label>
                        <input type="email" id="email" name="email" placeholder="wmsu.edu.ph">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password">
            </div>
            <div class="form-group">
                <label for="proof" class="form-label">Photo of your COR:</label>
                <input type="file" class="form-control" id="proof" name="proof" accept="image/*">
            </div>
            <button class="req" type="submit" id="request-account">Create Account</button>
        </form>
    </div>
</body>
</html>