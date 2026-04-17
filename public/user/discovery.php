<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/sidebar.php');
include(__DIR__ . '/includes/topbar.php');

include(__DIR__ . '/../../app/config/config.php');

// Get all categories
$sqlCategories = "SELECT DISTINCT category_id FROM books WHERE category_id IS NOT NULL ORDER BY category_id ASC";
$resultCategories = $conn->query($sqlCategories);
$categories = [];
if ($resultCategories && $resultCategories->num_rows > 0) {
    while ($row = $resultCategories->fetch_assoc()) {
        $categories[] = $row['category_id'];
    }
}

// Get books grouped by category
$booksByCategory = [];
foreach ($categories as $categoryId) {
    $sql = "SELECT uuid, title, author, publisher, yearPublished, category_id, description FROM books WHERE category_id = ? LIMIT 8";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $books = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['image'] = '../api/get-book-image.php?uuid=' . $row['uuid'];
            $books[] = $row;
        }
    }
    $stmt->close();
    
    if (!empty($books)) {
        $booksByCategory[$categoryId] = $books;
    }
}

// Get 4 random recommended books
$sqlRecommended = "SELECT uuid, title, author, publisher, yearPublished, category_id, description FROM books ORDER BY RAND() LIMIT 4";
$resultRecommended = $conn->query($sqlRecommended);
$recommendedBooks = [];
if ($resultRecommended && $resultRecommended->num_rows > 0) {
    while ($row = $resultRecommended->fetch_assoc()) {
        $row['image'] = '../api/get-book-image.php?uuid=' . $row['uuid'];
        $recommendedBooks[] = $row;
    }
}   

// Get 4 most recent books
$sqlNew = "SELECT uuid, title, author, publisher, yearPublished, category_id, description FROM books ORDER BY book_id DESC LIMIT 4";
$resultNew = $conn->query($sqlNew);
$newBooks = [];
if ($resultNew && $resultNew->num_rows > 0) {
    while ($row = $resultNew->fetch_assoc()) {
        $row['image'] = '../api/get-book-image.php?uuid=' . $row['uuid'];
        $newBooks[] = $row;
    }
}

$searchQuery = '';
$searchResults = [];
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchQuery = trim($_GET['search']);
    $searchTerm = '%' . $searchQuery . '%';
    $sqlSearch = "SELECT uuid, title, author, publisher, yearPublished, category_id, description FROM books WHERE title LIKE ? OR author LIKE ? ORDER BY title ASC LIMIT 20";
    $stmtSearch = $conn->prepare($sqlSearch);
    $stmtSearch->bind_param("ss", $searchTerm, $searchTerm);
    $stmtSearch->execute();
    $resultSearch = $stmtSearch->get_result();
    if ($resultSearch && $resultSearch->num_rows > 0) {
        while ($row = $resultSearch->fetch_assoc()) {
            $row['image'] = '../api/get-book-image.php?uuid=' . $row['uuid'];
            $searchResults[] = $row;
        }
    }
    $stmtSearch->close();
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Discovery Page</h1>
    </div>

    <?php if ($searchQuery !== ''): ?>
    <div class="section mb-5">
        <h2 class="section-title">Search results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
        <?php if (!empty($searchResults)): ?>
            <div class="books-grid">
                <?php foreach ($searchResults as $book): ?>
                    <div class="book-card" onclick="showBookDetail('<?php echo htmlspecialchars($book['uuid']); ?>')">
                        <img src="<?php echo $book['image']; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p class="book-author">Author: <?php echo htmlspecialchars($book['author']); ?></p>
                        <p><?php echo htmlspecialchars(substr($book['description'], 0, 100)) . '...'; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No books matched your search.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Recommended Books Section -->
    <div class="section mb-5">
        <h2 class="section-title">Recommended Books</h2>
        <div class="books-grid">
            <?php foreach ($recommendedBooks as $book): ?>
                <div class="book-card" onclick="showBookDetail('<?php echo htmlspecialchars($book['uuid']); ?>')">
                    <img src="<?php echo $book['image']; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p class="book-author">Author: <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><?php echo htmlspecialchars(substr($book['description'], 0, 100)) . '...'; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Popular Books Section -->
    <div class="section mb-5">
        <h2 class="section-title">New Books</h2>
        <div class="books-grid">
            <?php foreach ($newBooks as $book): ?>
                <div class="book-card" onclick="showBookDetail('<?php echo htmlspecialchars($book['uuid']); ?>')">
                    <img src="<?php echo $book['image']; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p class="book-author">Author: <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><?php echo htmlspecialchars(substr($book['description'], 0, 100)) . '...'; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Genre Sections -->
    <?php foreach ($booksByCategory as $categoryId => $books): ?>
    <div class="section mb-5">
        <h2 class="section-title">Genre <?php echo htmlspecialchars($categoryId); ?></h2>
        <div class="books-grid">
            <?php foreach ($books as $book): ?>
                <div class="book-card" onclick="showBookDetail('<?php echo htmlspecialchars($book['uuid']); ?>')">
                    <img src="<?php echo $book['image']; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p class="book-author">Author: <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><?php echo htmlspecialchars(substr($book['description'], 0, 100)) . '...'; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>
<!-- /.container-fluid -->

<!-- Book Detail Modal -->
<div id="bookDetailModal" class="book-modal">
    <div class="book-modal-content">
        <span class="book-modal-close">&times;</span>
        <div class="book-detail-container">
            <div class="book-detail-image">
                <img id="bookDetailImage" src="" alt="">
            </div>
            <div class="book-detail-info">
                <h2 id="bookDetailTitle"></h2>
                <p class="book-detail-author"><strong>Author:</strong> <span id="bookDetailAuthor"></span></p>
                <p class="book-detail-publisher"><strong>Publisher:</strong> <span id="bookDetailPublisher"></span></p>
                <p class="book-detail-year"><strong>Year Published:</strong> <span id="bookDetailYear"></span></p>
                <p class="book-detail-description"><strong>Description:</strong></p>
                <p id="bookDetailDescription"></p>
                <button id="bookButton" class="btn btn-primary mt-3">Book This</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('bookDetailModal');
        const closeBtn = document.querySelector('.book-modal-close');
        const bookButton = document.getElementById('bookButton');
        let currentBookUuid = null;

        function closeModal() {
            modal.style.display = 'none';
            currentBookUuid = null;
        }

        closeBtn.addEventListener('click', closeModal);

        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        window.showBookDetail = function(uuid) {
            currentBookUuid = uuid;
            bookButton.disabled = true;
            bookButton.textContent = 'Loading...';

            fetch('../api/get-book-details.php?uuid=' + encodeURIComponent(uuid))
                .then(response => response.json())
                .then(book => {
                    if (!book || book.error) {
                        throw new Error(book.error || 'Book details not found');
                    }

                    document.getElementById('bookDetailTitle').textContent = book.title || 'Untitled';
                    document.getElementById('bookDetailAuthor').textContent = book.author || 'Unknown';
                    document.getElementById('bookDetailPublisher').textContent = book.publisher || 'N/A';
                    document.getElementById('bookDetailYear').textContent = book.yearPublished || 'N/A';
                    document.getElementById('bookDetailDescription').textContent = book.description || 'No description available.';
                    document.getElementById('bookDetailImage').src = '../api/get-book-image.php?uuid=' + encodeURIComponent(uuid);
                    document.getElementById('bookDetailImage').alt = book.title || 'Book cover';

                    modal.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error loading book details:', error);
                    alert('Error loading book details. Please try again.');
                })
                .finally(() => {
                    bookButton.disabled = false;
                    bookButton.textContent = 'Book This';
                });
        };

        bookButton.addEventListener('click', function() {
            if (!currentBookUuid) {
                alert('Please select a book first.');
                return;
            }

            bookButton.disabled = true;
            bookButton.textContent = 'Booking...';

            fetch('../api/reserve-book.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ uuid: currentBookUuid })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Book reserved successfully! Redirecting to your reservations.');
                    window.location.href = 'barrowing.php';
                } else {
                    alert('Error: ' + (data.message || 'Could not reserve the book'));
                }
            })
            .catch(error => {
                console.error('Error reserving book:', error);
                alert('Error reserving the book. Please try again.');
            })
            .finally(() => {
                bookButton.disabled = false;
                bookButton.textContent = 'Book This';
            });
        });
    });
</script>

<?php
include(__DIR__ . '/includes/footer.php');
?>