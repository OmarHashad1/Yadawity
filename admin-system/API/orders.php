<?php
require_once "db.php";

header('Content-Type: application/json');

// Require admin authentication for all operations
require_admin();

// Check session timeout
check_session_timeout();

$allowedMethods = ['GET','POST','PUT','PATCH','DELETE','OPTIONS','HEAD'];

function send_json($d,int $s=200){http_response_code($s);echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
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
		$includeItems = isset($_GET['include_items']) && ($_GET['include_items']==='1' || $_GET['include_items']==='true');
		if($id){
			$stmt=$pdo->prepare('SELECT * FROM orders WHERE id=?');
			$stmt->execute([$id]);
			$order=$stmt->fetch(PDO::FETCH_ASSOC);
			if(!$order){send_json(['error'=>'Order not found'],404);} 
			if($includeItems){
				$its=$pdo->prepare('SELECT * FROM order_items WHERE order_id=?');
				$its->execute([$id]);
				$order['items']=$its->fetchAll(PDO::FETCH_ASSOC);
			}
			send_json(['data'=>$order]);
		}
		$page=max(1,(int)($_GET['page']??1));
	$limit=min(100,max(1,(int)($_GET['limit']??20)));
		$offset=($page-1)*$limit;
		$status=isset($_GET['status'])?$_GET['status']:null;
		$order_date=isset($_GET['order_date'])?$_GET['order_date']:null;
		$where=[];
		$params=[];
		if($status){
			$where[]='status = ?';
			$params[]=$status;
		}
		if($order_date){
			$where[]='order_date = ?';
			$params[]=$order_date;
		}
		$whereClause=$where?('WHERE '.implode(' AND ',$where)) : '';
		$sql="SELECT SQL_CALC_FOUND_ROWS * FROM orders $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
		$required=['order_number','buyer_id','buyer_name','total_amount'];
		foreach($required as $r){ if(!isset($body[$r])||$body[$r]===''){ send_json(['error'=>"Missing field: $r"],422);} }
		$status=$body['status']??'pending';
		$validStatus=['pending','confirmed','shipped','delivered','cancelled'];
		if(!in_array($status,$validStatus,true)){ send_json(['error'=>'Invalid status'],422);} 
		$pdo->beginTransaction();
		$stmt=$pdo->prepare('INSERT INTO orders (order_number,buyer_id,buyer_name,total_amount,status,shipping_address,order_date) VALUES (:order_number,:buyer_id,:buyer_name,:total_amount,:status,:shipping_address,:order_date)');
		$stmt->execute([
			':order_number'=>$body['order_number'],
			':buyer_id'=>(int)$body['buyer_id'],
			':buyer_name'=>$body['buyer_name'],
			':total_amount'=>(float)$body['total_amount'],
			':status'=>$status,
			':shipping_address'=>$body['shipping_address']??null,
			':order_date'=>$body['order_date']??date('Y-m-d'),
		]);
		$orderId=(int)$pdo->lastInsertId();
		if(isset($body['items']) && is_array($body['items'])){
			$itStmt=$pdo->prepare('INSERT INTO order_items (order_id,artwork_id,artwork_title,artist_id,price,quantity,subtotal) VALUES (:order_id,:artwork_id,:artwork_title,:artist_id,:price,:quantity,:subtotal)');
			foreach($body['items'] as $it){
				$it=sanitize($it);
				if(!isset($it['artwork_id'],$it['artwork_title'],$it['price'])){ continue; }
				$q=(int)($it['quantity']??1);
				$itStmt->execute([
					':order_id'=>$orderId,
					':artwork_id'=>(int)$it['artwork_id'],
					':artwork_title'=>$it['artwork_title'],
					':artist_id'=>isset($it['artist_id'])?(string)$it['artist_id']:null,
					':price'=>(float)$it['price'],
					':quantity'=>$q,
					':subtotal'=>(float)($it['price']*$q),
				]);
			}
		}
		$pdo->commit();
		send_json(['message'=>'Order created','id'=>$orderId],201);
	}

	if($method==='PUT'||$method==='PATCH'){
		$id=isset($_GET['id'])?(int)$_GET['id']:0; if($id<=0){send_json(['error'=>'Missing id'],400);} 
		$body=sanitize(parse_json_body());
		$allowed=['order_number','buyer_id','buyer_name','total_amount','status','shipping_address','order_date'];
		$fields=[];$params=[':id'=>$id];
		foreach($allowed as $k){ if(array_key_exists($k,$body)){ if($k==='status'){ $valid=['pending','confirmed','shipped','delivered','cancelled']; if(!in_array($body[$k],$valid,true)){ send_json(['error'=>'Invalid status'],422);} } $fields[]="$k = :$k"; $params[":$k"]=$body[$k]; } }
		if(empty($fields)){ send_json(['error'=>'No fields to update'],400);} 
		$sql='UPDATE orders SET '.implode(', ',$fields).' WHERE id = :id'; $stmt=$pdo->prepare($sql); $stmt->execute($params);
		send_json(['message'=>'Order updated']);
	}

	if($method==='DELETE'){
		$id=isset($_GET['id'])?(int)$_GET['id']:0; if($id<=0){send_json(['error'=>'Missing id'],400);} 
		$stmt=$pdo->prepare('DELETE FROM orders WHERE id=?'); $stmt->execute([$id]);
		if($stmt->rowCount()===0){ send_json(['error'=>'Order not found'],404);} 
		send_json(['message'=>'Order deleted']);
	}

	method_not_allowed($allowedMethods);
}catch(PDOException $e){$code=$e->errorInfo[1]??0; if($code===1062){send_json(['error'=>'Duplicate entry'],409);} send_json(['error'=>'Database error','details'=>$e->getMessage()],500);}catch(Throwable $e){send_json(['error'=>'Server error','details'=>$e->getMessage()],500);} 


