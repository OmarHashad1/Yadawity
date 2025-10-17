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

require_csrf_for_write();

try {
	if (!isset($pdo) || !($pdo instanceof PDO)) { throw new RuntimeException('Database connection not available'); }

	if ($method === 'GET') {
		$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
		if ($id) {
			$stmt = $pdo->prepare('SELECT * FROM artworks WHERE artwork_id = ?');
			$stmt->execute([$id]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$row) { send_json(['error' => 'Artwork not found'], 404); }
			send_json(['data' => $row]);
		}
		$page = max(1, (int)($_GET['page'] ?? 1));
		$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
		$offset = ($page - 1) * $limit;
		$type = isset($_GET['type']) ? $_GET['type'] : null;
		$status = isset($_GET['status']) ? $_GET['status'] : null;
		$auction = isset($_GET['auction']) ? $_GET['auction'] : null;

		$where = [];
		$params = [];
		$paramIndex = 1;
		if ($type) {
			$where[] = 'type = ?';
			$params[] = $type;
		}
		if ($status !== null && ($status === '0' || $status === '1')) {
			$where[] = 'is_available = ?';
			$params[] = (int)$status;
		}
		if ($auction !== null && ($auction === '0' || $auction === '1')) {
			$where[] = 'on_auction = ?';
			$params[] = (int)$auction;
		}
		$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM artworks $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
		$stmt = $pdo->prepare($sql);
		// Bind filter params
		foreach ($params as $i => $val) {
			$stmt->bindValue($i + 1, $val);
		}
		// Bind limit and offset as integers
		$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
		$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
		send_json(['data' => $rows, 'meta' => ['page'=>$page,'limit'=>$limit,'total'=>$total]]);
	}

	if ($method === 'POST') {
		$body = sanitize(parse_json_body());
		$required = ['artist_id','title','price','type'];
		foreach ($required as $r) { if (!isset($body[$r]) || $body[$r] === '') { send_json(['error' => "Missing field: $r"], 422); } }
		$validTypes = ['painting','sculpture','photography','digital','mixed_media','other'];
		if (!in_array($body['type'], $validTypes, true)) { send_json(['error' => 'Invalid type'], 422); }
		$stmt = $pdo->prepare('INSERT INTO artworks (artist_id, title, description, price, dimensions, year, material, artwork_image, type, is_available, on_auction) VALUES (:artist_id,:title,:description,:price,:dimensions,:year,:material,:artwork_image,:type,:is_available,:on_auction)');
		$stmt->execute([
			':artist_id' => (int)$body['artist_id'],
			':title' => (string)$body['title'],
			':description' => $body['description'] ?? null,
			':price' => (float)$body['price'],
			':dimensions' => $body['dimensions'] ?? null,
			':year' => isset($body['year']) ? (string)$body['year'] : null,
			':material' => $body['material'] ?? null,
			':artwork_image' => $body['artwork_image'] ?? null,
			':type' => (string)$body['type'],
			':is_available' => isset($body['is_available']) ? (int)!!$body['is_available'] : 1,
			':on_auction' => isset($body['on_auction']) ? (int)!!$body['on_auction'] : 0,
		]);
		$id = (int)$pdo->lastInsertId();
		send_json(['message' => 'Artwork created', 'id' => $id], 201);
	}

	if ($method === 'PUT' || $method === 'PATCH') {
		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		if ($id <= 0) { send_json(['error' => 'Missing id'], 400); }
		$body = sanitize(parse_json_body());
		$allowed = ['title','description','price','dimensions','year','material','artwork_image','type','is_available','on_auction'];
		$fields = [];
		$params = [':id' => $id];
		foreach ($allowed as $key) {
			if (array_key_exists($key, $body)) {
				if ($key === 'type') {
					$validTypes = ['painting','sculpture','photography','digital','mixed_media','other'];
					if (!in_array($body[$key], $validTypes, true)) { send_json(['error' => 'Invalid type'], 422); }
				}
				$fields[] = "$key = :$key";
				$params[":$key"] = $body[$key];
			}
		}
		if (empty($fields)) { send_json(['error' => 'No fields to update'], 400); }
		$sql = 'UPDATE artworks SET ' . implode(', ', $fields) . ' WHERE artwork_id = :id';
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		send_json(['message' => 'Artwork updated']);
	}

	if ($method === 'DELETE') {
		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		if ($id <= 0) { send_json(['error' => 'Missing id'], 400); }
		$stmt = $pdo->prepare('DELETE FROM artworks WHERE artwork_id = ?');
		$stmt->execute([$id]);
		if ($stmt->rowCount() === 0) { send_json(['error' => 'Artwork not found'], 404); }
		send_json(['message' => 'Artwork deleted']);
	}

	method_not_allowed($allowedMethods);
} catch (PDOException $e) { $code=$e->errorInfo[1]??0; if($code===1062){ send_json(['error'=>'Duplicate entry'],409);} send_json(['error'=>'Database error','details'=>$e->getMessage()],500);} catch (Throwable $e) { send_json(['error'=>'Server error','details'=>$e->getMessage()],500);} 


