<?php
session_start();
include '../functions/google_fonts.php';
include '../adviser-view/approvalAdviser.php';
require_once '../functions/functions.php';
require_once '../databases/connect.php';
require_once '../databases/database.class.php';
require_once '../databases/faculty.classes.php';

$db = new Database();
$user = new faculty($db);

$id = $_SESSION['ids'];
$get_type = $user->get_type($id);
$department_id = $_SESSION['department_id'];
$year_level = $_SESSION['year_level'];
$search = null;
$submissions = $user->get_adviser_excuse_letter($department_id);
$sections = $user->get_program($department_id, $year_level);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $search = clean_input(($_POST['search']));
    $filter = clean_input(($_POST['filter']));

    $submissions = $user->get_adviser_excuse_letter($department_id, $search, $filter);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexuse</title>

    <?php 
    includeGoogleFont([
        'Josefin Sans:ital,wght@0,100..700;1,100..700', 
        'Poppins:wght@400;600'
    ]); 
    ?>

    <script src="https://kit.fontawesome.com/3c9d5fece1.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" 
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="adviserStyle.css">
</head>
<body>
    <!-- SIDEBAR AREA -->
    <div class="sidebar">
        <div class="top">
            <div class="logo">
                <img src="/nexuse/images/Nexuse.svg" class="cat">
                <span class="text-cat">Nexuse.</span>
            </div>
            <i class="fa-solid fa-bars" id="sbtn"></i>
        </div>
        <ul class="sidebar-icons">
            <li>
                <a href="../adviser-view/adviser.php">
                    <i class="fa-solid fa-house-chimney-user"></i>
                    <span class="nav-item">Home</span>
                </a>
            </li>
            <li>
                <a href="../adviser-view/adviser.php">
                    <i class="fa-solid fa-inbox"></i>
                    <span class="nav-item">Submissions</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fa-solid fa-gear"></i>
                    <span class="nav-item">Settings</span>
                </a>
            </li>
            <li>
                <a href="../login/logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span class="nav-item">Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- NAVBAR -->
        <nav class="navbar">
            <div class="navbar-brand">
                <a href="#" class="site-title">Home Page.</a>
            </div>
            <div class="navbar-icons">
                <img src="/nexuse/images/lebron.jpg" alt="Profile Icon" class="icon-image">
            </div>
        </nav>

        <!-- HEADER -->
        <div class="user-p-cont">
            <div class="user-profile">
            <img src="/nexuse/images/lebron.jpg" alt="User Image" class="profile-image">
            <div class="profile-info">
                <h4 class="profile-name"><?=$_SESSION['last_name'] . ', ' . $_SESSION['first_name'] . ' ' . (!empty($_SESSION['middle_name']) ? $_SESSION['middle_name'] : '') ?></h4>
                <span class="profile-class"><?=$get_type?> Department, <?=$year_level?> Year Adviser</span>
            </div>
        </div>
        <div class="subject-container">
              <a class="year-level"><?=$year_level?> Year</a>
                <a class="subject-title"><?=$get_type?></a>
                 <!-- SEARCH AND FILTER FORM -->
            <div class="filter-searching">
                <form method="POST" class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Search by Name..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="filter" class="form-select">
                            <option value="">Filter by Section</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= clean_input($section['name']) ?>" <?= isset($_POST['filter']) && clean_input($_POST['filter']) === $section['name'] ? 'selected' : '' ?>>
                                    <?= clean_input($section['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-danger">Search</button>
                        <a href="adviser.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
                <!-- TABLE -->
                <table class="table table-bordered equal-width-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Professor</th>
                            <th>Date of Absent</th>
                            <th>Date of Submission</th>
                            <th>Remarks</th>
                            <th>Reason for Absence</th>
                            <th>Photo</th>
                            <th>Approval</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Filter submissions based on expiry date condition and pending status
                        $filtered_submissions = array_filter($submissions, function ($submission) {
                            $login_date = DateTime::createFromFormat('Y-m-d H:i:s', $_SESSION['login']);

                            // Check for pending submissions
                            if (empty($submission['date_approved']) && $submission['approval'] === 'Pending') {
                                return true;
                            }

                            // Check for approved submissions within expiry date
                            if (!empty($submission['date_approved'])) {
                                $date_approved = DateTime::createFromFormat('Y-m-d H:i:s', $submission['date_approved']);
                                if ($date_approved !== false) {
                                    $expiry_date = clone $date_approved;
                                    $expiry_date->modify('+7 days');
                                    return $login_date <= $expiry_date;
                                }
                            }

                            return false;
                        });

                        if (empty($filtered_submissions)): ?>
                            <tr>
                                <td colspan="10" class="text-ewan" style="text-align: center;">No submissions found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filtered_submissions as $submission): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #C70039;"><?= $submission['student_name'] ?></td>
                                    <td><?= $submission['name'] ?></td>
                                    <td><?= $submission['subject_name'] ?></td>
                                    <td><?= $submission['professor_name'] ?></td>
                                    <td><?= $submission['date_absent'] ?></td>
                                    <td><?= $submission['date_submitted'] ?></td>
                                    <td class="scrollable-cell"><?= htmlspecialchars($submission['comment']) ?></td>
                                    <td><?= $submission['type'] ?></td>
                                    <td>
                                        <img src="<?= $submission['excuse_letter'] ?>" alt="Photo" class="img-thumbnail photo-thumbnail" style="width:60px; cursor:pointer;" data-bs-toggle="modal" data-bs-target="#photoModal" data-photo="<?= $submission['excuse_letter'] ?>">
                                    </td>
                                    <?php if ($submission['approval'] == "Pending"): ?>
                                    <td>
                                        <div class="approvalButtons">
                                            <button class="yesApp-button" data-bs-toggle="modal" data-bs-target="#approvalButtons" data-action="approve" data-name="<?= $submission['student_name'] ?>" data-course="<?= $submission['name'] ?>" data-date-absent="<?= $submission['date_absent'] ?>" data-id="<?= $submission['approval_id'] ?>">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button class="notApp-button" data-bs-toggle="modal" data-bs-target="#approvalButtons" data-action="decline" data-name="<?= $submission['student_name'] ?>" data-course="<?= $submission['name'] ?>" data-date-absent="<?= $submission['date_absent'] ?>" data-id="<?= $submission['approval_id'] ?>">
                                                <i class="fa-solid fa-x"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <?php elseif ($submission['approval'] == "Approved"): ?>
                                    <td>
                                        <div class="approvalArea">
                                            <div class="approved">Approved</div>
                                        </div>
                                    </td>
                                    <?php elseif ($submission['approval'] == "Denied"): ?>
                                    <td>
                                        <div class="approvalArea">
                                            <div class="disapproved">Denied</div>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- PHOTO MODAL -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel">Photo of a documents/proof</h5>
                <button type="button" class="fa-regular fa-circle-xmark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <img id="modalPhoto" src="" alt="Full Image" class="img-fluid w-100 h-100" style="object-fit: contain;">
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // sidebar burger button
    const btn = document.querySelector("#sbtn");
     const sidebar = document.querySelector(".sidebar");
     btn.addEventListener("click", () => {
     sidebar.classList.toggle("active");
    });
 
    document.addEventListener('DOMContentLoaded', function () {
        const photoModal = document.getElementById('photoModal');
        const modalPhoto = document.getElementById('modalPhoto');

        // Add event listeners to all thumbnails
        document.querySelectorAll('.photo-thumbnail').forEach(thumbnail => {
            thumbnail.addEventListener('click', function () {
                const photoSrc = this.getAttribute('data-photo');
                modalPhoto.setAttribute('src', photoSrc);
            });
        });
    });


    </script>
</body>
</html>
