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
			$stmt=$pdo->prepare('SELECT * FROM galleries WHERE gallery_id=?');
			$stmt->execute([$id]);
			$row=$stmt->fetch(PDO::FETCH_ASSOC); if(!$row){send_json(['error'=>'Gallery not found'],404);} 
			send_json(['data'=>$row]);
		}
		$page=max(1,(int)($_GET['page']??1));$limit=min(100,max(1,(int)($_GET['limit']??20)));$offset=($page-1)*$limit; 
		$isActive = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
		if($isActive!==null){ $stmt=$pdo->prepare('SELECT SQL_CALC_FOUND_ROWS * FROM galleries WHERE is_active = ? ORDER BY created_at DESC LIMIT ? OFFSET ?'); $stmt->bindValue(1,$isActive,PDO::PARAM_INT); $stmt->bindValue(2,$limit,PDO::PARAM_INT); $stmt->bindValue(3,$offset,PDO::PARAM_INT); $stmt->execute(); }
		else { $stmt=$pdo->prepare('SELECT SQL_CALC_FOUND_ROWS * FROM galleries ORDER BY created_at DESC LIMIT ? OFFSET ?'); $stmt->bindValue(1,$limit,PDO::PARAM_INT); $stmt->bindValue(2,$offset,PDO::PARAM_INT); $stmt->execute(); }
		$rows=$stmt->fetchAll(PDO::FETCH_ASSOC); $total=(int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
		send_json(['data'=>$rows,'meta'=>['page'=>$page,'limit'=>$limit,'total'=>$total]]);
	}

	if($method==='POST'){
		$body=sanitize(parse_json_body());
		$required=['artist_id','title','gallery_type','duration'];
		foreach($required as $r){ if(!isset($body[$r])||$body[$r]===''){ send_json(['error'=>"Missing field: $r"],422);} }
		$validType=['virtual','physical']; if(!in_array($body['gallery_type'],$validType,true)){ send_json(['error'=>'Invalid gallery_type'],422);} 
		$stmt=$pdo->prepare('INSERT INTO galleries (artist_id,title,description,gallery_type,price,address,city,phone,start_date,duration,is_active,img) VALUES (:artist_id,:title,:description,:gallery_type,:price,:address,:city,:phone,:start_date,:duration,:is_active,:img)');
		$stmt->execute([
			':artist_id'=>(int)$body['artist_id'],
			':title'=>$body['title'],
			':description'=>$body['description']??null,
			':gallery_type'=>$body['gallery_type'],
			':price'=>isset($body['price'])?(float)$body['price']:null,
			':address'=>$body['address']??null,
			':city'=>$body['city']??null,
			':phone'=>$body['phone']??null,
			':start_date'=>$body['start_date']??date('Y-m-d H:i:s'),
			':duration'=>(int)$body['duration'],
			':is_active'=>isset($body['is_active'])?(int)!!$body['is_active']:1,
			':img'=>$body['img']??null,
		]);
		$id=(int)$pdo->lastInsertId();
		send_json(['message'=>'Gallery created','id'=>$id],201);
	}

	if($method==='PUT'||$method==='PATCH'){
		$id=isset($_GET['id'])?(int)$_GET['id']:0; if($id<=0){send_json(['error'=>'Missing id'],400);} 
		$body=sanitize(parse_json_body());
		$allowed=['title','description','gallery_type','price','address','city','phone','start_date','duration','is_active','img'];
		$fields=[];$params=[':id'=>$id];
		foreach($allowed as $k){ if(array_key_exists($k,$body)){ if($k==='gallery_type'){ $valid=['virtual','physical']; if(!in_array($body[$k],$valid,true)){ send_json(['error'=>'Invalid gallery_type'],422);} } $fields[]="$k = :$k"; $params[":$k"]=$body[$k]; } }
		if(empty($fields)){ send_json(['error'=>'No fields to update'],400);} 
		$sql='UPDATE galleries SET '.implode(', ',$fields).' WHERE gallery_id = :id'; $stmt=$pdo->prepare($sql); $stmt->execute($params);
		send_json(['message'=>'Gallery updated']);
	}

	if($method==='DELETE'){
		$id=isset($_GET['id'])?(int)$_GET['id']:0; if($id<=0){send_json(['error'=>'Missing id'],400);} 
		$stmt=$pdo->prepare('DELETE FROM galleries WHERE gallery_id=?'); $stmt->execute([$id]);
		if($stmt->rowCount()===0){ send_json(['error'=>'Gallery not found'],404);} 
		send_json(['message'=>'Gallery deleted']);
	}

	method_not_allowed($allowedMethods);
}catch(PDOException $e){$code=$e->errorInfo[1]??0; if($code===1062){send_json(['error'=>'Duplicate entry'],409);} send_json(['error'=>'Database error','details'=>$e->getMessage()],500);}catch(Throwable $e){send_json(['error'=>'Server error','details'=>$e->getMessage()],500);} 


