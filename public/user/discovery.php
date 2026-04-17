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
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Discovery Page</h1>
    </div>

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

<style>
    .book-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .book-modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 30px;
        border-radius: 8px;
        max-width: 800px;
        max-height: 80vh;
        overflow-y: auto;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from { 
            transform: translateY(-50px);
            opacity: 0;
        }
        to { 
            transform: translateY(0);
            opacity: 1;
        }
    }

    .book-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
    }

    .book-modal-close:hover,
    .book-modal-close:focus {
        color: black;
    }

    .book-detail-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 20px;
    }

    .book-detail-image {
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .book-detail-image img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .book-detail-info {
        padding: 10px;
    }

    .book-detail-info h2 {
        color: #333;
        font-size: 28px;
        margin-bottom: 15px;
    }

    .book-detail-author,
    .book-detail-publisher,
    .book-detail-year {
        margin-bottom: 10px;
        font-size: 16px;
        color: #555;
    }

    .book-detail-description {
        margin-top: 20px;
        line-height: 1.6;
        color: #666;
        font-size: 15px;
    }

    #bookButton {
        padding: 10px 30px;
        font-size: 16px;
        font-weight: 500;
        width: 100%;
    }

    .book-card {
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 768px) {
        .book-detail-container {
            grid-template-columns: 1fr;
        }

        .book-modal-content {
            margin: 20% auto;
            padding: 20px;
        }
    }
</style>

<script>
    const modal = document.getElementById('bookDetailModal');
    const closeBtn = document.querySelector('.book-modal-close');
    const bookButton = document.getElementById('bookButton');
    let currentBookUuid = null;

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    function showBookDetail(uuid) {
        currentBookUuid = uuid;
        
        fetch('../api/get-book-details.php?uuid=' + encodeURIComponent(uuid))
            .then(response => response.json())
            .then(book => {
                document.getElementById('bookDetailTitle').textContent = book.title;
                document.getElementById('bookDetailAuthor').textContent = book.author;
                document.getElementById('bookDetailPublisher').textContent = book.publisher || 'N/A';
                document.getElementById('bookDetailYear').textContent = book.yearPublished || 'N/A';
                document.getElementById('bookDetailDescription').textContent = book.description;
                document.getElementById('bookDetailImage').src = '../api/get-book-image.php?uuid=' + encodeURIComponent(uuid);
                
                modal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error loading book details:', error);
                alert('Error loading book details. Please try again.');
            });
    }

    bookButton.onclick = function() {
        if (!currentBookUuid) {
            alert('Book not selected');
            return;
        }

        // Send reservation request
        fetch('../api/reserve-book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                uuid: currentBookUuid
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Book reserved successfully! You can view it in your reservations.');
                modal.style.display = 'none';
            } else {
                alert('Error: ' + (data.message || 'Could not reserve the book'));
            }
        })
        .catch(error => {
            console.error('Error reserving book:', error);
            alert('Error reserving the book. Please try again.');
        });
    }
</script>

<?php
include(__DIR__ . '/includes/footer.php');
?>