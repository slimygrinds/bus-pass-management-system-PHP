<?php
/**
 * Database Connection Configuration (Dual Engine: MySQL + SQLite Fallback)
 * 
 * Attempts PDO connection to MySQL first.
 * If MySQL fails or is unauthenticated, automatically falls back to a 
 * self-contained SQLite database with custom MySQL-compatible functions (CURDATE, NOW, DATE_FORMAT).
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

// Database credentials for MySQL
$db_host = '127.0.0.1';
$db_port = '3307';
$db_name = 'bus_pass_db';
$db_user = 'root';
$db_pass = '';

$pdo = null;

// ==========================================
// 1. Try MySQL Connection
// ==========================================
try {
    $pdo = new PDO(
        "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // MySQL not reachable or credentials failed -> Fallback to SQLite
    $pdo = null;
}

// ==========================================
// 2. SQLite Fallback (Auto-initializes schema + seed data)
// ==========================================
if (!$pdo) {
    $sqlitePath = __DIR__ . '/../database/bus_pass_db.sqlite';
    $isNewDb = !file_exists($sqlitePath);

    try {
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Register custom functions to support MySQL date functions in SQLite
        $pdo->sqliteCreateFunction('CURDATE', function () {
            return date('Y-m-d');
        });

        $pdo->sqliteCreateFunction('NOW', function () {
            return date('Y-m-d H:i:s');
        });

        $pdo->sqliteCreateFunction('DATE', function ($dateStr = null) {
            if (!$dateStr || $dateStr === 'now')
                return date('Y-m-d');
            return date('Y-m-d', strtotime($dateStr));
        });

        $pdo->sqliteCreateFunction('DATE_FORMAT', function ($dateStr, $format) {
            if (!$dateStr)
                return '';
            $time = strtotime($dateStr);
            if ($format === '%Y-%m')
                return date('Y-m', $time);
            if ($format === '%Y')
                return date('Y', $time);
            return date('Y-m-d', $time);
        });

        // Initialize Schema & Seed Data if DB is new
        if ($isNewDb) {
            initSQLiteDatabase($pdo);
        }

    } catch (PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
}

/**
 * Initialize SQLite database structure and insert default seed data
 */
function initSQLiteDatabase($pdo)
{
    $queries = [
        "CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            phone TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );",

        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            full_name TEXT NOT NULL,
            father_name TEXT,
            gender TEXT,
            dob DATE,
            age INTEGER,
            mobile TEXT,
            email TEXT UNIQUE NOT NULL,
            address TEXT,
            city TEXT,
            state TEXT,
            pincode TEXT,
            aadhar_number TEXT,
            photo TEXT DEFAULT 'default-avatar.png',
            security_question TEXT,
            security_answer TEXT,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );",

        "CREATE TABLE IF NOT EXISTS bus_routes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            route_code TEXT UNIQUE NOT NULL,
            source TEXT NOT NULL,
            destination TEXT NOT NULL,
            distance_km REAL,
            fare REAL,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );",

        "CREATE TABLE IF NOT EXISTS buses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            route_id INTEGER NOT NULL,
            bus_number TEXT UNIQUE NOT NULL,
            bus_name TEXT NOT NULL,
            bus_type TEXT DEFAULT 'Non-AC',
            capacity INTEGER DEFAULT 50,
            driver_name TEXT,
            status TEXT DEFAULT 'active',
            pass_available TEXT DEFAULT 'Yes',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS timetable (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            route_id INTEGER NOT NULL,
            bus_id INTEGER NOT NULL,
            departure_time TEXT NOT NULL,
            arrival_time TEXT NOT NULL,
            days_of_operation TEXT DEFAULT 'Mon-Sat',
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE CASCADE,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            route_id INTEGER NOT NULL,
            application_number TEXT UNIQUE NOT NULL,
            full_name TEXT NOT NULL,
            father_name TEXT NOT NULL,
            gender TEXT NOT NULL,
            dob DATE NOT NULL,
            age INTEGER NOT NULL,
            mobile TEXT NOT NULL,
            email TEXT NOT NULL,
            address TEXT NOT NULL,
            city TEXT NOT NULL,
            state TEXT NOT NULL,
            pincode TEXT NOT NULL,
            aadhar_number TEXT NOT NULL,
            boarding_point TEXT NOT NULL,
            destination TEXT NOT NULL,
            pass_duration TEXT NOT NULL,
            photo TEXT,
            student_id_doc TEXT,
            status TEXT DEFAULT 'pending',
            remarks TEXT,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS passes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            application_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            pass_number TEXT UNIQUE NOT NULL,
            issue_date DATE NOT NULL,
            expiry_date DATE NOT NULL,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            application_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            transaction_id TEXT UNIQUE NOT NULL,
            status TEXT DEFAULT 'completed',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS feedback (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject TEXT NOT NULL,
            message TEXT NOT NULL,
            status TEXT DEFAULT 'new',
            admin_reply TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_type TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );",

        // Insert Admin (Admin / Admin@123)
        "INSERT INTO admins (username, password, full_name, email, phone) VALUES
        ('Admin', '\$2y\$10\$7pXh5NBc7/suctVghm56EuhPLIeg25iqhPV9fZobmGmpqFK2zSb1a', 'System Administrator', 'admin@buspass.com', '9876543210');",

        // Insert Sample Users (Student@123)
        "INSERT INTO users (username, password, full_name, father_name, gender, dob, age, mobile, email, address, city, state, pincode, aadhar_number, security_question, security_answer) VALUES
        ('rahul.sharma', '\$2y\$10\$bnO1jivy7fXUdcULECNAsuZ82uE6NLZCSHej02190LGBEQRfVixhe', 'Rahul Sharma', 'Mahesh Sharma', 'Male', '2003-05-15', 23, '9876543211', 'rahul.sharma@email.com', '12, Navrangpura', 'Ahmedabad', 'Gujarat', '380009', '123456789012', 'What is your pet name?', 'bruno'),
        ('priya.patel', '\$2y\$10\$bnO1jivy7fXUdcULECNAsuZ82uE6NLZCSHej02190LGBEQRfVixhe', 'Priya Patel', 'Rajesh Patel', 'Female', '2004-02-20', 22, '9876543212', 'priya.patel@email.com', '45, Alkapuri', 'Vadodara', 'Gujarat', '390007', '234567890123', 'What is your birth city?', 'vadodara'),
        ('amit.joshi', '\$2y\$10\$bnO1jivy7fXUdcULECNAsuZ82uE6NLZCSHej02190LGBEQRfVixhe', 'Amit Joshi', 'Suresh Joshi', 'Male', '2003-11-10', 22, '9876543213', 'amit.joshi@email.com', '78, University Road', 'Rajkot', 'Gujarat', '360005', '345678901234', 'What is your school name?', 'dps');",

        // Insert Bus Routes
        "INSERT INTO bus_routes (route_code, source, destination, distance_km, fare) VALUES
        ('RT001', 'Ahmedabad', 'Rajkot', 220.00, 250.00),
        ('RT002', 'Ahmedabad', 'Vadodara', 112.00, 150.00),
        ('RT003', 'Ahmedabad', 'Surat', 265.00, 300.00),
        ('RT004', 'Vadodara', 'Surat', 160.00, 180.00),
        ('RT005', 'Vadodara', 'Ahmedabad', 112.00, 150.00),
        ('RT006', 'Rajkot', 'Junagadh', 102.00, 120.00),
        ('RT007', 'Rajkot', 'Ahmedabad', 220.00, 250.00),
        ('RT008', 'Surat', 'Vadodara', 160.00, 180.00),
        ('RT009', 'Surat', 'Ahmedabad', 265.00, 300.00),
        ('RT010', 'Ahmedabad', 'Gandhinagar', 30.00, 40.00),
        ('RT011', 'Ahmedabad', 'Mehsana', 80.00, 100.00),
        ('RT012', 'Vadodara', 'Anand', 45.00, 60.00),
        ('RT013', 'Rajkot', 'Jamnagar', 90.00, 110.00),
        ('RT014', 'Surat', 'Vapi', 170.00, 200.00),
        ('RT015', 'Surat', 'Valsad', 120.00, 140.00),
        ('RT016', 'Ahmedabad', 'Bhavnagar', 200.00, 230.00),
        ('RT017', 'Vadodara', 'Bharuch', 72.00, 90.00),
        ('RT018', 'Ahmedabad', 'Palanpur', 140.00, 160.00);",

        // Insert Buses
        "INSERT INTO buses (route_id, bus_number, bus_name, bus_type, capacity, driver_name, pass_available) VALUES
        (1, 'GJ-01-AB-1234', 'Ahmedabad Express', 'AC', 45, 'Ramesh Patel', 'Yes'),
        (1, 'GJ-01-CD-5678', 'Saurashtra Mail', 'Non-AC', 55, 'Sunil Mehta', 'Yes'),
        (2, 'GJ-06-EF-9012', 'Vadodara Volvo', 'AC', 40, 'Dinesh Shah', 'Yes'),
        (2, 'GJ-06-GH-3456', 'Baroda Express', 'Non-AC', 55, 'Kiran Desai', 'Yes'),
        (3, 'GJ-01-IJ-7890', 'Surat Superfast', 'AC', 45, 'Manoj Kumar', 'Yes'),
        (4, 'GJ-06-KL-2345', 'Golden Bridge', 'Non-AC', 50, 'Bharat Rana', 'Yes'),
        (6, 'GJ-03-MN-6789', 'Gir Express', 'Non-AC', 55, 'Jayesh Solanki', 'Yes'),
        (7, 'GJ-03-OP-0123', 'Rajkot Rider', 'AC', 45, 'Nilesh Joshi', 'Yes'),
        (10, 'GJ-01-QR-4567', 'Capital Connect', 'Non-AC', 50, 'Ashok Thakor', 'Yes'),
        (11, 'GJ-01-ST-8901', 'Mehsana Express', 'Non-AC', 55, 'Vijay Pandya', 'Yes'),
        (13, 'GJ-03-UV-2345', 'Jamnagar Jet', 'AC', 40, 'Pravin Doshi', 'Yes'),
        (14, 'GJ-05-WX-6789', 'South Gujarat Express', 'Semi-Sleeper', 45, 'Kamlesh Naik', 'Yes');",

        // Insert Timetable
        "INSERT INTO timetable (route_id, bus_id, departure_time, arrival_time, days_of_operation) VALUES
        (1, 1, '06:00:00', '10:00:00', 'Mon-Sat'),
        (1, 2, '08:30:00', '13:00:00', 'Mon-Sat'),
        (2, 3, '07:00:00', '09:00:00', 'Mon-Sat'),
        (2, 4, '09:30:00', '11:30:00', 'Mon-Fri'),
        (3, 5, '06:30:00', '12:00:00', 'Daily'),
        (4, 6, '07:30:00', '10:30:00', 'Mon-Sat'),
        (6, 7, '08:00:00', '10:00:00', 'Mon-Sat'),
        (7, 8, '14:00:00', '18:00:00', 'Mon-Sat'),
        (10, 9, '07:00:00', '07:45:00', 'Mon-Fri'),
        (11, 10, '06:30:00', '08:30:00', 'Mon-Sat'),
        (13, 11, '09:00:00', '11:00:00', 'Mon-Sat'),
        (14, 12, '07:00:00', '11:00:00', 'Daily');",

        // Insert Notifications
        "INSERT INTO notifications (user_id, title, message) VALUES
        (1, 'Welcome to Bus Pass System', 'Your account has been created successfully. You can now apply for a bus pass.'),
        (1, 'Application Reminder', 'Don''t forget to apply for your bus pass before the semester starts.'),
        (2, 'Welcome to Bus Pass System', 'Your account has been created successfully. You can now apply for a bus pass.');"
    ];

    foreach ($queries as $q) {
        $pdo->exec($q);
    }
}
?>