<?php
require_once "db.php";

header('Content-Type: application/json');

// Require admin authentication for all operations
require_admin();

// Check session timeout
check_session_timeout();

$allowedMethods=['GET','POST','PUT','PATCH','DELETE','OPTIONS','HEAD'];

function send_json($d,$s=200){http_response_code($s);echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function method_not_allowed($m){header('Allow: '.implode(', ',$m));send_json(['error'=>'Method Not Allowed'],405);} 
function parse_json_body(){ $r=file_get_contents('php://input'); $b=json_decode($r,true); return is_array($b)?$b:[]; }
function sanitize($v){ return is_array($v)?array_map('sanitize',$v):htmlspecialchars(trim((string)$v),ENT_QUOTES,'UTF-8'); }
// CSRF protection is now handled by require_csrf_for_write() from auth.php

$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method==='HEAD'){header('Allow: '.implode(', ',$allowedMethods));exit;}
if($method==='OPTIONS'){header('Allow: '.implode(', ',$allowedMethods));header('Access-Control-Allow-Methods: '.implode(', ',$allowedMethods));header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');exit;}
if(!in_array($method,$allowedMethods,true)){method_not_allowed($allowedMethods);} 

require_csrf_for_write();

try{
	if(!isset($pdo)||!($pdo instanceof PDO)){throw new RuntimeException('Database connection not available');}

	if($method==='GET'){
		$id=isset($_GET['id'])?(int)$_GET['id']:null;
		if($id){
			$stmt=$pdo->prepare('SELECT * FROM auctions WHERE id=?');
			$stmt->execute([$id]);
			$row=$stmt->fetch(PDO::FETCH_ASSOC); if(!$row){send_json(['error'=>'Auction not found'],404);} 
			send_json(['data'=>$row]);
		}
		$page=max(1,(int)($_GET['page']??1));
		$limit=min(100,max(1,(int)($_GET['limit']??20)));
		$offset=($page-1)*$limit;
		$status=$_GET['status']??null;
		$auction_date=$_GET['auction_date']??null;
		$where=[];
		$params=[];
		if($status){
			$where[]='status = ?';
			$params[]=$status;
		}
		if($auction_date){
			$where[]='DATE(start_time) = ?';
			$params[]=$auction_date;
		}
		$whereClause=$where?('WHERE '.implode(' AND ',$where)) : '';
		$sql="SELECT SQL_CALC_FOUND_ROWS * FROM auctions $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
		$stmt=$pdo->prepare($sql);
		foreach($params as $i=>$val){
			$stmt->bindValue($i+1,$val);
		}
		$stmt->bindValue(count($params)+1,$limit,PDO::PARAM_INT);
		$stmt->bindValue(count($params)+2,$offset,PDO::PARAM_INT);
		$stmt->execute();
		$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
		$total=(int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
		send_json(['data'=>$rows,'meta'=>['page'=>$page,'limit'=>$limit,'total'=>$total]]);
	}

	if($method==='POST'){
		$body=sanitize(parse_json_body());
		$required=['product_id','artist_id','starting_bid','start_time','end_time'];
		foreach($required as $r){ if(!isset($body[$r])||$body[$r]===''){ send_json(['error'=>"Missing field: $r"],422);} }
		$status=$body['status']??'upcoming'; $valid=['upcoming','starting_soon','active','sold','cancelled']; if(!in_array($status,$valid,true)){ send_json(['error'=>'Invalid status'],422);} 
		$stmt=$pdo->prepare('INSERT INTO auctions (product_id,artist_id,starting_bid,current_bid,start_time,end_time,status) VALUES (:product_id,:artist_id,:starting_bid,:current_bid,:start_time,:end_time,:status)');
		$stmt->execute([
			':product_id'=>(int)$body['product_id'],
			':artist_id'=>(int)$body['artist_id'],
			':starting_bid'=>(float)$body['starting_bid'],
			':current_bid'=>isset($body['current_bid'])?(float)$body['current_bid']:0.00,
			':start_time'=>$body['start_time'],
			':end_time'=>$body['end_time'],
			':status'=>$status,
		]);
		$id=(int)$pdo->lastInsertId();
		send_json(['message'=>'Auction created','id'=>$id],201);
	}

	if($method==='PUT'||$method==='PATCH'){
		$id=isset($_GET['id'])?(int)$_GET['id']:0; if($id<=0){send_json(['error'=>'Missing id'],400);} 
		$body=sanitize(parse_json_body());
		$allowed=['product_id','artist_id','starting_bid','current_bid','start_time','end_time','status'];
		$fields=[];$params=[':id'=>$id];
		foreach($allowed as $k){ if(array_key_exists($k,$body)){ if($k==='status'){ $valid=['upcoming','starting_soon','active','sold','cancelled']; if(!in_array($body[$k],$valid,true)){ send_json(['error'=>'Invalid status'],422);} } $fields[]="$k = :$k"; $params[":$k"]=$body[$k]; } }
		if(empty($fields)){ send_json(['error'=>'No fields to update'],400);} 
		$sql='UPDATE auctions SET '.implode(', ',$fields).' WHERE id = :id'; $stmt=$pdo->prepare($sql); $stmt->execute($params);
		send_json(['message'=>'Auction updated']);
	}

	if($method==='DELETE'){
		$id=isset($_GET['id'])?(int)$_GET['id']:0; if($id<=0){send_json(['error'=>'Missing id'],400);} 
		$stmt=$pdo->prepare('DELETE FROM auctions WHERE id=?'); $stmt->execute([$id]);
		if($stmt->rowCount()===0){ send_json(['error'=>'Auction not found'],404);} 
		send_json(['message'=>'Auction deleted']);
	}

	method_not_allowed($allowedMethods);
}catch(PDOException $e){$code=$e->errorInfo[1]??0; if($code===1062){send_json(['error'=>'Duplicate entry'],409);} send_json(['error'=>'Database error','details'=>$e->getMessage()],500);}catch(Throwable $e){send_json(['error'=>'Server error','details'=>$e->getMessage()],500);} 


