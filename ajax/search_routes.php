<?php
/**
 * AJAX: Search Routes
 * 
 * Handles two actions:
 * 1. 'suggest' - Returns route suggestions matching the search query
 * 2. 'search'  - Returns buses and timetable for a specific route
 * 
 * Used by the autocomplete search on the Search Routes page.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

session_start();

require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

// ==========================================
// Action: Suggest (Autocomplete)
// ==========================================
if ($action === 'suggest') {
    $query = sanitize($_GET['q'] ?? '');
    
    if (strlen($query) < 1) {
        echo json_encode([]);
        exit();
    }
    
    // Search routes where source or destination matches the query
    $searchTerm = "%{$query}%";
    $stmt = $pdo->prepare("SELECT id, route_code, source, destination, distance_km, fare 
                            FROM bus_routes 
                            WHERE status = 'active' 
                            AND (source LIKE ? OR destination LIKE ? OR route_code LIKE ?)
                            ORDER BY source, destination 
                            LIMIT 10");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $routes = $stmt->fetchAll();
    
    echo json_encode($routes);
    exit();
}

// ==========================================
// Action: Search (Full Results)
// ==========================================
if ($action === 'search') {
    $routeCode = sanitize($_GET['route_code'] ?? '');
    
    if (empty($routeCode)) {
        echo json_encode([]);
        exit();
    }
    
    // Get buses and timetable for this route
    $stmt = $pdo->prepare("SELECT b.bus_number, b.bus_name, b.bus_type, b.capacity, b.pass_available,
                                   r.source, r.destination, r.route_code,
                                   t.departure_time, t.arrival_time, t.days_of_operation
                            FROM buses b
                            JOIN bus_routes r ON b.route_id = r.id
                            LEFT JOIN timetable t ON t.bus_id = b.id AND t.status = 'active'
                            WHERE r.route_code = ? AND b.status = 'active'
                            ORDER BY t.departure_time");
    $stmt->execute([$routeCode]);
    $results = $stmt->fetchAll();
    
    // Format time for display
    foreach ($results as &$row) {
        if (!empty($row['departure_time'])) {
            $row['departure_time'] = date('h:i A', strtotime($row['departure_time']));
        }
        if (!empty($row['arrival_time'])) {
            $row['arrival_time'] = date('h:i A', strtotime($row['arrival_time']));
        }
    }
    
    echo json_encode($results);
    exit();
}

// Default: empty response
echo json_encode([]);
?>
