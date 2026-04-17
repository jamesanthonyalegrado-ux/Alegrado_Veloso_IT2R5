<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

session_start();

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/sidebar.php');
include(__DIR__ . '/includes/topbar.php');
include(__DIR__ . '/../../app/config/config.php');

// 🔒 DB CHECK
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// 🔒 LOGIN CHECK
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);

// ======================
// SAFE DATA FETCH
// ======================

// Total Books
$totalBooks = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM books");
if ($res) $totalBooks = $res->fetch_assoc()['total'];

// Check reservations table
$tableCheck = $conn->query("SHOW TABLES LIKE 'reservations'");
$reservationsExists = ($tableCheck && $tableCheck->num_rows > 0);

// My reservations
$myReservations = 0;
if ($reservationsExists && $userId > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservations WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) $myReservations = $res->fetch_assoc()['total'];
    $stmt->close();
}
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Welcome to your Library 📚</h1>

    <!-- STATS -->
    <div class="row">

        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary">Total Books</div>
                    <div class="h5 font-weight-bold text-gray-800"><?php echo $totalBooks; ?></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success">My Reservations</div>
                    <div class="h5 font-weight-bold text-gray-800"><?php echo $myReservations; ?></div>
                </div>
            </div>
        </div>

    </div>

    <!-- QUICK ACTIONS -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <a href="discovery.php" class="btn btn-primary btn-block mb-2">📚 Explore Library</a>
                    <a href="reservations.php" class="btn btn-success btn-block mb-2">📖 My Reservations</a>
                    <a href="history.php" class="btn btn-warning btn-block mb-2">🕒 Borrow History</a>
                    <a href="profile.php" class="btn btn-info btn-block">👤 My Profile</a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include(__DIR__ . '/includes/footer.php'); ?>