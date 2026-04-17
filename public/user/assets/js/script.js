// script.js - Interactivity for Discovery Page

document.addEventListener('DOMContentLoaded', function() {
    // Hover animation is handled by CSS, but we can add more JS interactions

    // Add click event to book cards for highlighting
    const bookCards = document.querySelectorAll('.book-card');
    bookCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove highlight from all cards
            bookCards.forEach(c => c.classList.remove('highlighted'));
            // Add highlight to clicked card
            this.classList.add('highlighted');
        });
    });

    // Search behavior for topbar search inputs
    const topbarSearchForm = document.getElementById('topbarSearchForm');
    const topbarSearchInput = document.getElementById('topbarSearchInput');
    const mobileSearchForm = document.getElementById('mobileSearchForm');
    const mobileSearchInput = document.getElementById('mobileSearchInput');

    function createSearchResultsContainer(input) {
        if (!input) return null;
        const wrapper = input.closest('.navbar-search');
        if (!wrapper) return null;

        let container = wrapper.querySelector('.search-results');
        if (!container) {
            container = document.createElement('div');
            container.className = 'search-results';
            wrapper.appendChild(container);
        }
        return container;
    }

    function clearSearchResults(container) {
        if (!container) return;
        container.innerHTML = '';
        container.style.display = 'none';
    }

    function renderSearchResults(items, container, query) {
        if (!container) return;
        container.innerHTML = '';

        if (!items || items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'search-results-item';
            empty.textContent = `No results found for "${query}"`;
            container.appendChild(empty);
            container.style.display = 'block';
            return;
        }

        items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'search-results-item';
            row.dataset.query = item.title || query;
            row.innerHTML = `<span>${item.title}</span><span>${item.author}</span>`;
            row.addEventListener('click', function() {
                const targetQuery = this.dataset.query || query;
                if (targetQuery) {
                    window.location.href = `discovery.php?search=${encodeURIComponent(targetQuery)}`;
                }
            });
            container.appendChild(row);
        });

        container.style.display = 'block';
    }

    function fetchSearchSuggestions(query, container) {
        if (!query || !container) {
            clearSearchResults(container);
            return;
        }

        fetch(`../api/search-book.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                renderSearchResults(data, container, query);
            })
            .catch(() => {
                clearSearchResults(container);
            });
    }

    function attachSearchBehavior(form, input) {
        if (!form || !input) return;
        const results = createSearchResultsContainer(input);

        input.addEventListener('input', function() {
            const query = this.value.trim();
            fetchSearchSuggestions(query, results);
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = input.value.trim();
            if (query) {
                window.location.href = `discovery.php?search=${encodeURIComponent(query)}`;
            } else {
                clearSearchResults(results);
            }
        });
    }

    attachSearchBehavior(topbarSearchForm, topbarSearchInput);
    attachSearchBehavior(mobileSearchForm, mobileSearchInput);

    document.addEventListener('click', function(event) {
        if (!event.target.closest('.navbar-search') && !event.target.closest('.search-results')) {
            clearSearchResults(createSearchResultsContainer(topbarSearchInput));
            clearSearchResults(createSearchResultsContainer(mobileSearchInput));
        }
    });

    // See More buttons (placeholder functionality)
    const seeMoreBtns = document.querySelectorAll('.see-more-btn');
    seeMoreBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            alert('Loading more books...');
            // In a real app, this would load more content dynamically
        });
    });

    // Read buttons (placeholder)
    const readBtns = document.querySelectorAll('.read-btn');
    readBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const bookTitle = this.parentElement.querySelector('h3').textContent;
            alert(`Opening: ${bookTitle}`);
            // In a real app, this would open the book or redirect to a reading page
        });
    });
});