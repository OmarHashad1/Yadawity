<?php
require_once "db.php";

header('Content-Type: application/json');

// Require admin authentication for all operations
require_admin();

// Check session timeout
check_session_timeout();

$allowedMethods = ['GET','POST','PUT','PATCH','DELETE','OPTIONS','HEAD'];

function send_json($data, int $status = 200): void { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function method_not_allowed(array $methods): void { header('Allow: ' . implode(', ', $methods)); send_json(['error' => 'Method Not Allowed'], 405); }
function parse_json_body(): array { $raw = file_get_contents('php://input'); $b = json_decode($raw, true); return is_array($b) ? $b : []; }
function sanitize($v){ return is_array($v) ? array_map('sanitize',$v) : htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8'); }
// CSRF protection is now handled by require_csrf_for_write() from auth.php

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'HEAD') { header('Allow: ' . implode(', ', $allowedMethods)); exit; }
if ($method === 'OPTIONS') { header('Allow: ' . implode(', ', $allowedMethods)); header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods)); header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token'); exit; }
if (!in_array($method, $allowedMethods, true)) { method_not_allowed($allowedMethods); }

// POST/PUT act as report generation requests (no persistence)
if (in_array($method, ['POST','PUT','PATCH'], true)) { require_csrf_for_write(); }

try {
	if (!isset($pdo) || !($pdo instanceof PDO)) { throw new RuntimeException('Database connection not available'); }

	if (in_array($method, ['GET','POST','PUT','PATCH'], true)) {
		$params = $method === 'GET' ? $_GET : sanitize(parse_json_body());
		$type = $params['type'] ?? 'sales_summary';
		$from = isset($params['from']) ? substr((string)$params['from'],0,10) : null;
		$to   = isset($params['to']) ? substr((string)$params['to'],0,10) : null;

		$payload = ['type' => $type, 'range' => ['from' => $from, 'to' => $to]];

		switch ($type) {
			case 'sales_summary':
				if ($from && $to) {
					$stmt = $pdo->prepare('SELECT order_date as date, COUNT(*) as orders, COALESCE(SUM(total_amount),0) as revenue, COALESCE(AVG(total_amount),0) as avg_order_value FROM orders WHERE order_date BETWEEN ? AND ? GROUP BY order_date ORDER BY order_date');
					$stmt->execute([$from, $to]);
				} else {
					$stmt = $pdo->query('SELECT order_date as date, COUNT(*) as orders, COALESCE(SUM(total_amount),0) as revenue, COALESCE(AVG(total_amount),0) as avg_order_value FROM orders GROUP BY order_date ORDER BY order_date DESC LIMIT 30');
				}
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$payload['data'] = ['sales_data' => $rows];
				break;

			case 'user_activity':
				if ($from && $to) {
					$stmt = $pdo->prepare('SELECT u.user_id, CONCAT(u.first_name, " ", u.last_name) as name, COUNT(s.session_id) as login_count, MAX(s.login_time) as last_login FROM users u LEFT JOIN user_login_sessions s ON u.user_id = s.user_id AND s.login_time BETWEEN ? AND ? GROUP BY u.user_id');
					$stmt->execute([$from, $to]);
				} else {
					$stmt = $pdo->query('SELECT u.user_id, CONCAT(u.first_name, " ", u.last_name) as name, COUNT(s.session_id) as login_count, MAX(s.login_time) as last_login FROM users u LEFT JOIN user_login_sessions s ON u.user_id = s.user_id GROUP BY u.user_id');
				}
				$payload['data'] = ['user_activity' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
				break;

			case 'artwork_performance':
				if ($from && $to) {
					$stmt = $pdo->prepare('SELECT a.artwork_id, a.title, 0 as views, COALESCE(SUM(oi.quantity),0) as sales, COALESCE(SUM(oi.subtotal),0) as revenue, 0 as rating FROM artworks a LEFT JOIN order_items oi ON a.artwork_id = oi.artwork_id LEFT JOIN orders o ON oi.order_id = o.id AND o.order_date BETWEEN ? AND ? GROUP BY a.artwork_id, a.title');
					$stmt->execute([$from, $to]);
				} else {
					$stmt = $pdo->query('SELECT a.artwork_id, a.title, 0 as views, COALESCE(SUM(oi.quantity),0) as sales, COALESCE(SUM(oi.subtotal),0) as revenue, 0 as rating FROM artworks a LEFT JOIN order_items oi ON a.artwork_id = oi.artwork_id GROUP BY a.artwork_id, a.title');
				}
				$payload['data'] = ['artwork_performance' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
				break;

			case 'auction_results':
				if ($from && $to) {
					$stmt = $pdo->prepare('SELECT id as auction_id, product_id, artist_id, starting_bid, current_bid, status, end_time as end_date FROM auctions WHERE end_time BETWEEN ? AND ? ORDER BY end_time DESC');
					$stmt->execute([$from, $to]);
				} else {
					$stmt = $pdo->query('SELECT id as auction_id, product_id, artist_id, starting_bid, current_bid, status, end_time as end_date FROM auctions ORDER BY end_time DESC');
				}
				$payload['data'] = ['auction_results' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
				break;

			case 'revenue_analysis':
				if ($from && $to) {
					$stmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) as revenue FROM orders WHERE order_date BETWEEN ? AND ?');
					$stmt->execute([$from, $to]);
				} else {
					$stmt = $pdo->query('SELECT COALESCE(SUM(total_amount),0) as revenue FROM orders');
				}
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				$payload['data'] = ['revenue_analysis' => $row];
				break;

			case 'inventory_status':
				$stmt = $pdo->query('SELECT artwork_id, title, CASE WHEN is_available=1 THEN "In Stock" ELSE "Out of Stock" END as status, created_at as last_updated FROM artworks ORDER BY created_at DESC LIMIT 200');
				$payload['data'] = ['inventory_status' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
				break;

			case 'artist_performance':
				if ($from && $to) {
					$sql = 'SELECT u.user_id, CONCAT(u.first_name, " ", u.last_name) as artist_name, SUM(oi.subtotal) as revenue, SUM(oi.quantity) as qty FROM order_items oi INNER JOIN users u ON oi.artist_id = u.user_id INNER JOIN orders o ON oi.order_id = o.id WHERE o.order_date BETWEEN ? AND ? GROUP BY u.user_id ORDER BY revenue DESC LIMIT 50';
					$stmt = $pdo->prepare($sql);
					$stmt->execute([$from, $to]);
				} else {
					$sql = 'SELECT u.user_id, CONCAT(u.first_name, " ", u.last_name) as artist_name, SUM(oi.subtotal) as revenue, SUM(oi.quantity) as qty FROM order_items oi INNER JOIN users u ON oi.artist_id = u.user_id GROUP BY u.user_id ORDER BY revenue DESC LIMIT 50';
					$stmt = $pdo->query($sql);
				}
				$payload['data'] = ['artist_performance' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
				break;

			default:
				send_json(['error' => 'Unsupported report type'], 422);
		}

		send_json($payload);
	}

	if ($method === 'DELETE') { method_not_allowed($allowedMethods); }

	method_not_allowed($allowedMethods);
} catch (PDOException $e) { send_json(['error' => 'Database error', 'details' => $e->getMessage()], 500); } catch (Throwable $e) { send_json(['error' => 'Server error', 'details' => $e->getMessage()], 500); }


