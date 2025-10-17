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

// POST accepted as analytical query with filters (non-persistent)
if (in_array($method, ['POST'], true)) { require_csrf_for_write(); }

try {
	if (!isset($pdo) || !($pdo instanceof PDO)) { throw new RuntimeException('Database connection not available'); }

	if ($method === 'GET' || $method === 'POST') {
		$params = $method === 'POST' ? sanitize(parse_json_body()) : $_GET;
		$from = isset($params['from']) ? substr((string)$params['from'],0,10) : null;
		$to   = isset($params['to']) ? substr((string)$params['to'],0,10) : null;

		$result = [];

		// Daily orders and revenue
		if ($from && $to) {
			$stmt = $pdo->prepare('SELECT order_date as date, COUNT(*) as count, COALESCE(SUM(total_amount),0) as revenue FROM orders WHERE order_date BETWEEN ? AND ? GROUP BY order_date ORDER BY order_date');
			$stmt->execute([$from, $to]);
		} else {
			$stmt = $pdo->query('SELECT order_date as date, COUNT(*) as count, COALESCE(SUM(total_amount),0) as revenue FROM orders GROUP BY order_date ORDER BY order_date DESC LIMIT 30');
		}
		$filtered_days = array_values(array_filter($stmt->fetchAll(PDO::FETCH_ASSOC), function($row) {
			return ($row['count'] > 0 || $row['revenue'] > 0);
		}));
		// Sort by date descending
		usort($filtered_days, function($a, $b) {
			return strtotime($b['date']) - strtotime($a['date']);
		});
		// Only keep the 10 most recent days with data
		$result['daily_orders'] = array_slice($filtered_days, 0, 10);

		// Total orders and total revenue (for cards)
		if ($from && $to) {
			$stmt = $pdo->prepare('SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount),0) as total_revenue FROM orders WHERE order_date BETWEEN ? AND ?');
			$stmt->execute([$from, $to]);
		} else {
			$stmt = $pdo->query('SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount),0) as total_revenue FROM orders');
		}
		$totals = $stmt->fetch(PDO::FETCH_ASSOC);
		$result['total_orders'] = (int)($totals['total_orders'] ?? 0);
		$result['total_revenue'] = (float)($totals['total_revenue'] ?? 0);

		// Active auctions (for card)
		$stmt = $pdo->query("SELECT COUNT(*) FROM auctions WHERE status = 'active'");
		$result['active_auctions'] = (int)$stmt->fetchColumn();

		// Artworks by type count (for card)
		$stmt = $pdo->query('SELECT COUNT(*) FROM artworks');
		$result['artworks_by_type_count'] = (int)$stmt->fetchColumn();

		// Top artworks by sales (by subtotal)
		if ($from && $to) {
			$q = 'SELECT oi.artwork_id, oi.artwork_title AS title, SUM(oi.subtotal) as revenue, SUM(oi.quantity) as sales_count FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id WHERE o.order_date BETWEEN ? AND ? GROUP BY oi.artwork_id, oi.artwork_title ORDER BY revenue DESC LIMIT 10';
			$stmt = $pdo->prepare($q); $stmt->execute([$from, $to]);
		} else {
			$q = 'SELECT artwork_id, artwork_title AS title, SUM(subtotal) as revenue, SUM(quantity) as sales_count FROM order_items GROUP BY artwork_id, artwork_title ORDER BY revenue DESC LIMIT 10';
			$stmt = $pdo->query($q);
		}
		$result['top_artworks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Auctions status counts
		$result['auctions_status'] = $pdo->query('SELECT status, COUNT(*) cnt FROM auctions GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR) ?: new stdClass();

		// Artworks by type
		$result['artworks_by_type'] = $pdo->query('SELECT type, COUNT(*) cnt FROM artworks GROUP BY type')->fetchAll(PDO::FETCH_KEY_PAIR) ?: new stdClass();

		send_json(['data' => $result, 'range' => ['from' => $from, 'to' => $to]]);
	}

	// Updates/deletes are not applicable for analytics
	method_not_allowed($allowedMethods);
} catch (PDOException $e) { send_json(['error' => 'Database error', 'details' => $e->getMessage()], 500); } catch (Throwable $e) { send_json(['error' => 'Server error', 'details' => $e->getMessage()], 500); }


