<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

session_start();

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/sidebar.php');
include(__DIR__ . '/includes/topbar.php');
include(__DIR__ . '/../../app/config/config.php');


if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$tableCheck = $conn->query("SHOW TABLES LIKE 'reservations'");
if ($tableCheck->num_rows == 0) {
    die("Error: reservations table missing");
}

$dateColumn = null;
$columnsCheck = $conn->query("SHOW COLUMNS FROM reservations");

while ($col = $columnsCheck->fetch_assoc()) {
    if (in_array($col['Field'], ['reservation_date', 'created_at', 'date_reserved'])) {
        $dateColumn = $col['Field'];
        break;
    }
}

if (!$dateColumn) {
    die("Error: No valid date column found (reservation_date / created_at / date_reserved)");
}

// ✅ Query (dynamic date column)
$sql = "SELECT r.*, b.uuid, b.title, b.author 
        FROM reservations r 
        JOIN books b ON r.book_id = b.book_id 
        WHERE r.user_id = ? 
        ORDER BY r.$dateColumn DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $row['image'] = '../api/get-book-image.php?uuid=' . $row['uuid'];
    $row['display_date'] = isset($row[$dateColumn]) ? $row[$dateColumn] : null;
    $reservations[] = $row;
}
$stmt->close();

// ✅ Group
$group = [
    'reserved' => [],
    'pending' => [],
    'collected' => [],
    'returned' => [],
    'cancelled' => []
];

foreach ($reservations as $r) {
    if (isset($group[$r['status']])) {
        $group[$r['status']][] = $r;
    }
}
?>


<div class="container-fluid">
    <h2 class="mb-4">My Reservations</h2>

    <?php
    function renderCards($title, $data, $status, $showCancel = false)
    {
        echo "<div class='section-title'>$title (" . count($data) . ")</div>";

        if (empty($data)) {
            echo "<p class='text-muted'>No records</p>";
            return;
        }

        foreach ($data as $r) {
            ?>
            <div class="book-card">
                <img src="<?php echo $r['image']; ?>" class="book-img">

                <div class="book-info">
                    <div class="book-title"><?php echo htmlspecialchars($r['title']); ?></div>
                    <div class="book-author"><?php echo htmlspecialchars($r['author']); ?></div>

                    <small>
                        Reserved:
                        <?php
                        echo $r['display_date']
                            ? date('M d, Y', strtotime($r['display_date']))
                            : 'N/A';
                        ?>
                    </small>
                </div>

                <div>
                    <span class="badge-status <?php echo $status; ?>">
                        <?php echo ucfirst($status); ?>
                    </span>

                    <?php if ($showCancel): ?>
                        <br><br>
                        <button class="btn btn-sm btn-danger" onclick="cancelReservation(<?php echo $r['reservation_id']; ?>)">
                            Cancel
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
    }
    ?>

    <?php
    renderCards("Reserved Books", $group['reserved'], "reserved", true);
    renderCards("Pending Books", $group['pending'], "pending");
    renderCards("Collected Books", $group['collected'], "collected");
    renderCards("Returned Books", $group['returned'], "returned");
    ?>

</div>

<script>
    function cancelReservation(id) {
        if (!confirm("Cancel reservation?")) return;

        fetch('../api/cancel-reservation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reservation_id: id })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Cancelled!");
                    location.reload();
                } else {
                    alert("Error: " + (data.message || "Failed"));
                }
            })
            .catch(err => {
                console.error(err);
                alert("Request failed");
            });
    }
</script>

<?php include(__DIR__ . '/includes/footer.php'); ?>