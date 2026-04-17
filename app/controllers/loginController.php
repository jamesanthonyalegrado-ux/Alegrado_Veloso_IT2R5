<?php
session_start();
$root = dirname(__DIR__);
include($root . '/config/config.php');

function _generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );



};


if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $loginQuery = "SELECT user_id, uuid, firstName, lastName, username, password, role 
                   FROM users 
                   WHERE username = ? AND password = ? 
                   LIMIT 1";
    $stmt = $conn->prepare($loginQuery);

    if ($stmt) {
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $database = $result->fetch_assoc();

            $user_id  = $database['user_id'];
            $fullname = $database['firstName'] . ' ' . $database['lastName'];
            $userRole = $database['role']; 

            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $userRole;
            $_SESSION['authUser'] = [
                'userid'   => $user_id,
                'fullname' => $fullname,
                'username' => $database['username'],
            ];

            $_SESSION['message'] = "Welcome! $fullname";
            $_SESSION['code'] = "success";

            if ($userRole === 'admin') {
                header("Location: /eLibrary/public/admin/index");
                exit();
            } else {
                header("Location: /eLibrary/public/user/index.html");
                exit();
            }
        } else {
            $_SESSION['message'] = "Invalid username or password.";
            $_SESSION['code'] = "warning";
            header("Location: /eLibrary/public/login");
            exit();
        }
    } else {
        $_SESSION['message'] = "Something went wrong: " . $conn->error . " Please try again.";
        $_SESSION['code'] = "warning";
        header("Location: /eLibrary/public/login");
        exit();
    }
}


if (isset($_POST['register'])) {
    $firstname = $_POST['firstName'];
    $middlename = $_POST['middleName'];
    $lastname = $_POST['lastName'];
    $email = $_POST['emailAddress'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $repeatPassword = $_POST['repeatPassword'];
    $street = $_POST['street'];
    $barangay = $_POST['barangay'];
    $city = $_POST['city'];
    $role = 'user'; 
    $uuid = _generateUUID();


    //Validates format of email address
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format.";
        $_SESSION['code'] = "error";
        header("Location: /eLibrary/public/register");
        exit();
    }

    //checks if emailAddress already exists in the database
    $checkQuery = mysqli_query($conn, "SELECT user_id from users where 
    emailAddress='$email' LIMIT 1"); 
    if ($checkQuery && mysqli_num_rows($checkQuery) > 0) {
        $_SESSION['message'] = "Email address already exists.";
        $_SESSION['code'] = "error";
        header("Location: /eLibrary/public/register");
        exit();
    }

    //checks if username already exists in the database
    $checkQuery = mysqli_query($conn, "SELECT user_id from users where 
    username='$username' LIMIT 1");
    if ($checkQuery && mysqli_num_rows($checkQuery) > 0) {
        $_SESSION['message'] = "Username already exists.";
        $_SESSION['code'] = "error";
        header("Location: /eLibrary/public/register");
        exit();
    }


    if ($password !== $repeatPassword) {
        $_SESSION['message'] = "Passwords do not match.";
        $_SESSION['code'] = "error";
        header("Location: /eLibrary/public/register");
        exit();
    }

   $stmt = $conn->prepare("INSERT INTO users 
    (uuid, firstName, middleName, lastName, emailAddress, username, password, street, barangay, city, role) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $uuid, $firstname, $middlename, $lastname, $email, $username, $password, $street, $barangay, $city, $role);
    


    if($stmt->execute()) {
        $_SESSION['message'] = "Registration successful! Please log in.";
        $_SESSION['code'] = "success";
        header("Location: /eLibrary/public/login");
        exit();
    } else {
        $_SESSION['message'] = "Something went wrong: " . mysqli_error($conn) . " Please try again.";
        $_SESSION['code'] = "error";
        header("Location: /eLibrary/public/register");
        exit();
    }

}

?>
