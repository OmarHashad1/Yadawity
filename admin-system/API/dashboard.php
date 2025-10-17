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

// Allow POST as an alternate to GET to pass complex filters in body (does not mutate state)
if (in_array($method, ['POST','PUT','PATCH','DELETE'], true)) { require_csrf_for_write(); }

try {
	if (!isset($pdo) || !($pdo instanceof PDO)) { throw new RuntimeException('Database connection not available'); }

	if ($method === 'GET' || $method === 'POST') {
		$params = $method === 'POST' ? sanitize(parse_json_body()) : $_GET;
		$from = isset($params['from']) ? substr((string)$params['from'],0,10) : null; // YYYY-MM-DD
		$to   = isset($params['to']) ? substr((string)$params['to'],0,10) : null;

		$metrics = [];

	$metrics['users_total'] = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
	$metrics['users_artists'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'artist'")->fetchColumn();
	$metrics['users_buyers'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'buyer'")->fetchColumn();
	$metrics['users_admins'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn();

	$metrics['artworks_total'] = (int)$pdo->query('SELECT COUNT(*) FROM artworks')->fetchColumn();
	$metrics['artworks_available'] = (int)$pdo->query('SELECT COUNT(*) FROM artworks WHERE is_available = 1')->fetchColumn();
	$metrics['artworks_on_auction'] = (int)$pdo->query('SELECT COUNT(*) FROM artworks WHERE on_auction = 1')->fetchColumn();
	$metrics['artworks_sold'] = (int)$pdo->query("SELECT COUNT(*) FROM artworks WHERE artwork_id IN (SELECT product_id FROM auctions WHERE status = 'sold')")->fetchColumn();

		$metrics['orders_total'] = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
		if ($from && $to) {
			$stmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE order_date BETWEEN ? AND ?');
			$stmt->execute([$from, $to]);
			$metrics['revenue_total'] = (float)$stmt->fetchColumn();
		} else {
			$metrics['revenue_total'] = (float)$pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM orders')->fetchColumn();
		}

	$ordersByStatus = $pdo->query('SELECT status, COUNT(*) cnt FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
	$metrics['orders_by_status'] = $ordersByStatus ?: new stdClass();

	$metrics['auctions_active'] = (int)$pdo->query("SELECT COUNT(*) FROM auctions WHERE status = 'active'")->fetchColumn();
	$metrics['auctions_upcoming'] = (int)$pdo->query("SELECT COUNT(*) FROM auctions WHERE status IN ('upcoming','starting_soon')")->fetchColumn();
	$metrics['auctions_sold'] = (int)$pdo->query("SELECT COUNT(*) FROM auctions WHERE status = 'sold'")->fetchColumn();

	// Gallery breakdown
	$metrics['galleries_physical'] = (int)$pdo->query("SELECT COUNT(*) FROM galleries WHERE gallery_type = 'physical'")->fetchColumn();
	$metrics['galleries_virtual'] = (int)$pdo->query("SELECT COUNT(*) FROM galleries WHERE gallery_type = 'virtual'")->fetchColumn();

	// Workshops and sessions
	$metrics['workshops_total'] = (int)$pdo->query('SELECT COUNT(*) FROM workshops')->fetchColumn();
	$metrics['sessions_total'] = (int)$pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn();

	$metrics['wishlists_total'] = (int)$pdo->query('SELECT COUNT(*) FROM wishlists')->fetchColumn();

		send_json(['data' => $metrics, 'range' => ['from' => $from, 'to' => $to]]);
	}

	// Non-mutating dashboard: other methods are not supported
	method_not_allowed($allowedMethods);
} catch (PDOException $e) { send_json(['error' => 'Database error', 'details' => $e->getMessage()], 500); } catch (Throwable $e) { send_json(['error' => 'Server error', 'details' => $e->getMessage()], 500); }


