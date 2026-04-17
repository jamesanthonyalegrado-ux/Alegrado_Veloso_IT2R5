<?php
session_start();
include('../../app/config/config.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get the book UUID from the request
$requestBody = json_decode(file_get_contents('php://input'), true);
if (!is_array($requestBody)) {
    $requestBody = $_POST;
}

if (!isset($requestBody['uuid']) || trim($requestBody['uuid']) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Book UUID is required']);
    exit;
}

$uuid = trim($requestBody['uuid']);
$user_id = intval($_SESSION['user_id']);
if ($user_id <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid user session. Please log in again.']);
    exit;
}

// First, check if the book exists
$sqlCheckBook = "SELECT book_id, uuid, title FROM books WHERE uuid = ?";
$stmtCheckBook = $conn->prepare($sqlCheckBook);
$stmtCheckBook->bind_param("s", $uuid);
$stmtCheckBook->execute();
$resultCheckBook = $stmtCheckBook->get_result();

if ($resultCheckBook->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    $stmtCheckBook->close();
    exit;
}

$book = $resultCheckBook->fetch_assoc();
$book_id = $book['book_id'];
$stmtCheckBook->close();

// Check if user has already reserved this book
$sqlCheckReservation = "SELECT reservation_id FROM reservations WHERE user_id = ? AND book_id = ? AND status IN ('reserved', 'pending')";
$stmtCheckReservation = $conn->prepare($sqlCheckReservation);
$stmtCheckReservation->bind_param("ii", $user_id, $book_id);
$stmtCheckReservation->execute();
$resultCheckReservation = $stmtCheckReservation->get_result();

if ($resultCheckReservation->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You have already reserved this book']);
    $stmtCheckReservation->close();
    exit;
}
$stmtCheckReservation->close();

// Insert the reservation into the database
$reservationDate = date('Y-m-d H:i:s');
$status = 'reserved';

$sqlInsertReservation = "INSERT INTO reservations (user_id, book_id, reservation_date, status) VALUES (?, ?, ?, ?)";
$stmtInsertReservation = $conn->prepare($sqlInsertReservation);

if (!$stmtInsertReservation) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmtInsertReservation->bind_param("iiss", $user_id, $book_id, $reservationDate, $status);

if ($stmtInsertReservation->execute()) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Book reserved successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error reserving book: ' . $stmtInsertReservation->error]);
}

$stmtInsertReservation->close();
$conn->close();
?>
