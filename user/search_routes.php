<?php
/**
 * Search Bus Routes
 * 
 * Google-like autocomplete route search using AJAX.
 * Shows suggestions as user types, displays matching buses and routes.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

$pageTitle = 'Search Bus Routes';
$currentPage = 'search_routes';

require_once '../includes/header.php';
requireUser();
require_once '../includes/user_sidebar.php';
?>

<!-- Breadcrumb -->
<nav class="page-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Search Bus Routes</li>
    </ol>
</nav>

<!-- Search Section -->
<div class="card form-card mb-4">
    <div class="card-header">
        <i class="bi bi-search me-2"></i>Search Bus Routes
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Search for bus routes by entering source or destination. Start typing to see suggestions.</p>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="search-container">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="routeSearchInput" 
                               placeholder="Type a city name (e.g., Ahmedabad, Vadodara...)"
                               autocomplete="off">
                    </div>
                    
                    <!-- Autocomplete Suggestions -->
                    <div class="autocomplete-list" id="suggestionList"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Results -->
<div class="card table-card" id="resultsCard" style="display: none;">
    <div class="card-header">
        <i class="bi bi-list-ul me-2"></i>Search Results
        <span class="badge bg-primary rounded-pill ms-2" id="resultCount">0</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Bus No.</th>
                        <th>Bus Name</th>
                        <th>Source</th>
                        <th>Destination</th>
                        <th>Departure</th>
                        <th>Arrival</th>
                        <th>Bus Type</th>
                        <th>Pass Available</th>
                    </tr>
                </thead>
                <tbody id="resultsBody">
                    <!-- Filled by AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Popular Routes -->
<div class="card table-card mt-4" id="popularRoutes">
    <div class="card-header">
        <i class="bi bi-star me-2"></i>Popular Routes
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4"><button class="btn btn-outline-primary w-100 route-chip" onclick="searchRoute('Ahmedabad')">Ahmedabad → Rajkot</button></div>
            <div class="col-md-4"><button class="btn btn-outline-primary w-100 route-chip" onclick="searchRoute('Vadodara')">Vadodara → Surat</button></div>
            <div class="col-md-4"><button class="btn btn-outline-primary w-100 route-chip" onclick="searchRoute('Surat')">Surat → Ahmedabad</button></div>
            <div class="col-md-4"><button class="btn btn-outline-primary w-100 route-chip" onclick="searchRoute('Rajkot')">Rajkot → Junagadh</button></div>
            <div class="col-md-4"><button class="btn btn-outline-primary w-100 route-chip" onclick="searchRoute('Gandhinagar')">Ahmedabad → Gandhinagar</button></div>
            <div class="col-md-4"><button class="btn btn-outline-primary w-100 route-chip" onclick="searchRoute('Mehsana')">Ahmedabad → Mehsana</button></div>
        </div>
    </div>
</div>

<script>
const searchInput = document.getElementById('routeSearchInput');
const suggestionList = document.getElementById('suggestionList');
const resultsCard = document.getElementById('resultsCard');
const resultsBody = document.getElementById('resultsBody');
const resultCount = document.getElementById('resultCount');
let debounceTimer;

/**
 * Handle input in search box - fetch suggestions via AJAX
 */
searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    // Clear previous timer
    clearTimeout(debounceTimer);
    
    if (query.length < 1) {
        suggestionList.classList.remove('show');
        suggestionList.innerHTML = '';
        return;
    }
    
    // Debounce: wait 200ms after user stops typing
    debounceTimer = setTimeout(function() {
        fetch('<?php echo BASE_URL; ?>ajax/search_routes.php?action=suggest&q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                suggestionList.innerHTML = '';
                
                if (data.length === 0) {
                    suggestionList.innerHTML = '<div class="autocomplete-item text-muted">No routes found</div>';
                    suggestionList.classList.add('show');
                    return;
                }
                
                data.forEach(function(route) {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.innerHTML = '<i class="bi bi-geo-alt"></i>' +
                        '<span class="route-text">' + route.source + ' → ' + route.destination + '</span>' +
                        '<br><span class="route-sub ms-4">' + route.route_code + ' | ' + route.distance_km + ' km | ₹' + route.fare + '</span>';
                    
                    item.addEventListener('click', function() {
                        searchInput.value = route.source + ' → ' + route.destination;
                        suggestionList.classList.remove('show');
                        fetchRouteResults(route.route_code);
                    });
                    
                    suggestionList.appendChild(item);
                });
                
                suggestionList.classList.add('show');
            })
            .catch(err => console.error('Search failed:', err));
    }, 200);
});

/**
 * Fetch full route results (buses + timetable)
 */
function fetchRouteResults(routeCode) {
    fetch('<?php echo BASE_URL; ?>ajax/search_routes.php?action=search&route_code=' + encodeURIComponent(routeCode))
        .then(response => response.json())
        .then(data => {
            resultsBody.innerHTML = '';
            resultCount.textContent = data.length;
            
            if (data.length === 0) {
                resultsBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">No buses found for this route.</td></tr>';
            } else {
                data.forEach(function(bus) {
                    resultsBody.innerHTML += `
                        <tr>
                            <td><strong>${bus.bus_number}</strong></td>
                            <td>${bus.bus_name}</td>
                            <td>${bus.source}</td>
                            <td>${bus.destination}</td>
                            <td>${bus.departure_time || 'N/A'}</td>
                            <td>${bus.arrival_time || 'N/A'}</td>
                            <td><span class="badge bg-info">${bus.bus_type}</span></td>
                            <td>${bus.pass_available === 'Yes' 
                                ? '<span class="badge bg-success">Yes</span>' 
                                : '<span class="badge bg-secondary">No</span>'}</td>
                        </tr>`;
                });
            }
            
            resultsCard.style.display = 'block';
            document.getElementById('popularRoutes').style.display = 'none';
        })
        .catch(err => console.error('Fetch results failed:', err));
}

/**
 * Search by popular route click
 */
function searchRoute(city) {
    searchInput.value = city;
    searchInput.dispatchEvent(new Event('input'));
}

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !suggestionList.contains(e.target)) {
        suggestionList.classList.remove('show');
    }
});

// Show suggestions on focus if there's already text
searchInput.addEventListener('focus', function() {
    if (this.value.trim().length >= 1 && suggestionList.innerHTML !== '') {
        suggestionList.classList.add('show');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
