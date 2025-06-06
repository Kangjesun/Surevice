<?php
session_start();
require __DIR__ . '/config.php';  

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    header("Location: ../login.php?error=" . urlencode("All fields are required"));
    exit;
}

$sql = "SELECT user_id, first_name, last_name, user_type, password_hash, account_status FROM Users WHERE email = ?";
$params = [$email];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if ($user) {
    // Block Suspended and Deactivated accounts
    if (in_array($user['account_status'], ['Suspended', 'Deactivated'])) {
        header("Location: ../login.php?error=" . urlencode("Your account is currently {$user['account_status']}. Please contact support."));
        exit;
    }

    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_type'] = $user['user_type'];

        switch ($user['user_type']) {
            case 'customer':
                header("Location: ../index.php");
                break;
            case 'provider':
                header("Location: ../dashboard/service-provider.php");
                break;
            case 'admin':
                header("Location: ../dashboard/admin.php");
                break;
            default:
                header("Location: ../login.php?error=" . urlencode("Unknown user type"));
        }
        exit;
    } else {
        header("Location: ../login.php?error=" . urlencode("Invalid email or password"));
        exit;
    }
} else {
    header("Location: ../login.php?error=" . urlencode("Invalid email or password"));
    exit;
}
