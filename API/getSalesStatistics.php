<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function checkAuthentication() {
    if (!isset($_COOKIE['user_login'])) {
        return ['success' => false, 'message' => 'Not authenticated'];
    }

    $cookie_data = $_COOKIE['user_login'];
    $decoded_data = base64_decode($cookie_data);
    $user_data = json_decode($decoded_data, true);
    
    if (!$user_data || !isset($user_data['user_id'])) {
        return ['success' => false, 'message' => 'Invalid session data'];
    }

    try {
        require_once 'db.php';
        global $pdo;
        
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        // Get session info
        $stmt = $pdo->prepare("SELECT session_id FROM user_login_sessions WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_data['user_id']]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            return ['success' => false, 'message' => 'No valid session found'];
        }
        
        // Get user email for hash verification
        $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->execute([$user_data['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Verify hash
        $expected_hash = hash_hmac('sha256', $cookie_data, $session['session_id'] . '_' . $user['email']);
        
        if (!isset($_COOKIE['user_login_hash']) || !hash_equals($expected_hash, $_COOKIE['user_login_hash'])) {
            return ['success' => false, 'message' => 'Invalid session hash'];
        }
        
        return ['success' => true, 'user_id' => $user_data['user_id']];
        
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication failed'];
    }
}

try {
    // Check authentication
    $auth_result = checkAuthentication();
    if (!$auth_result['success']) {
        echo json_encode($auth_result);
        exit;
    }
    
    $user_id = $auth_result['user_id'];
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 30; // Default to 30 days
    
    require_once 'db.php';
    global $pdo;
    
    if (!$pdo) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }
    
    // Get sales data over time (count of artworks sold per day/week/month depending on period)
    if ($days <= 7) {
        // Daily data for last 7 days
        $sql = "SELECT 
                    DATE(o.order_date) as period,
                    COUNT(oi.id) as sales_count,
                    COALESCE(SUM(oi.subtotal), 0) as total_amount
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.artist_id = ? 
                AND o.order_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND o.status IN ('confirmed', 'paid', 'shipped', 'delivered')
                GROUP BY DATE(o.order_date)
                ORDER BY period ASC";
        $label_format = 'M j'; // Jan 1
    } else if ($days <= 30) {
        // Daily data for last 30 days (but we'll group by week for cleaner display)
        $sql = "SELECT 
                    YEARWEEK(o.order_date, 1) as week_num,
                    DATE(DATE_SUB(o.order_date, INTERVAL WEEKDAY(o.order_date) DAY)) as period,
                    COUNT(oi.id) as sales_count,
                    COALESCE(SUM(oi.subtotal), 0) as total_amount
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.artist_id = ? 
                AND o.order_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND o.status IN ('confirmed', 'paid', 'shipped', 'delivered')
                GROUP BY YEARWEEK(o.order_date, 1)
                ORDER BY period ASC";
        $label_format = 'M j'; // Week starting Jan 1
    } else {
        // Monthly data for longer periods
        $sql = "SELECT 
                    DATE_FORMAT(o.order_date, '%Y-%m-01') as period,
                    COUNT(oi.id) as sales_count,
                    COALESCE(SUM(oi.subtotal), 0) as total_amount
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.artist_id = ? 
                AND o.order_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND o.status IN ('confirmed', 'paid', 'shipped', 'delivered')
                GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
                ORDER BY period ASC";
        $label_format = 'M Y'; // Jan 2024
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $days]);
    $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create arrays for chart data
    $labels = [];
    $sales_amounts = [];
    $sales_counts = [];
    
    // Fill in missing days/weeks/months with zero values
    $start_date = new DateTime();
    $start_date->sub(new DateInterval('P' . $days . 'D'));
    $end_date = new DateTime();
    
    // Create a lookup array from the database results
    $data_lookup = [];
    foreach ($sales_data as $row) {
        $data_lookup[$row['period']] = [
            'amount' => (float)$row['total_amount'],
            'count' => (int)$row['sales_count']
        ];
    }
    
    // Generate all periods and fill with data or zeros
    if ($days <= 7) {
        // Daily periods
        $current = clone $start_date;
        while ($current <= $end_date) {
            $period_key = $current->format('Y-m-d');
            $labels[] = $current->format($label_format);
            
            if (isset($data_lookup[$period_key])) {
                $sales_amounts[] = $data_lookup[$period_key]['amount'];
                $sales_counts[] = $data_lookup[$period_key]['count'];
            } else {
                $sales_amounts[] = 0;
                $sales_counts[] = 0;
            }
            
            $current->add(new DateInterval('P1D'));
        }
    } else if ($days <= 30) {
        // Weekly periods
        $current = clone $start_date;
        $current->modify('Monday this week'); // Start from Monday
        
        while ($current <= $end_date) {
            $period_key = $current->format('Y-m-d');
            $labels[] = 'Week of ' . $current->format($label_format);
            
            if (isset($data_lookup[$period_key])) {
                $sales_amounts[] = $data_lookup[$period_key]['amount'];
                $sales_counts[] = $data_lookup[$period_key]['count'];
            } else {
                $sales_amounts[] = 0;
                $sales_counts[] = 0;
            }
            
            $current->add(new DateInterval('P1W'));
        }
    } else {
        // Monthly periods
        $current = clone $start_date;
        $current->modify('first day of this month');
        
        while ($current <= $end_date) {
            $period_key = $current->format('Y-m-01');
            $labels[] = $current->format($label_format);
            
            if (isset($data_lookup[$period_key])) {
                $sales_amounts[] = $data_lookup[$period_key]['amount'];
                $sales_counts[] = $data_lookup[$period_key]['count'];
            } else {
                $sales_amounts[] = 0;
                $sales_counts[] = 0;
            }
            
            $current->add(new DateInterval('P1M'));
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'sales_amounts' => $sales_amounts,
            'sales_counts' => $sales_counts,
            'period_days' => $days
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Sales statistics error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch sales statistics'
    ]);
}
?>
