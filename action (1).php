<?php require 'lib.php'; need_login(); $m=me();
if($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals(csrf(),$_POST['csrf']??'')){http_response_code(400);exit('Requisição inválida.');}
$a=$_POST['a']??''; $id=(int)($_POST['id']??0); $body=trim($_POST['body']??'');
$back=$_POST['back']??''; if(!preg_match('/^(index\.php|profile\.php\?u=[A-Za-z0-9_]{3,20})$/',$back))$back='index.php';
$mu=$_POST['media_url']??''; $mt=$_POST['media_type']??''; $has=media_ok($mu,$mt);
if($a==='post'&&mb_strlen($body)<=500&&($body!==''||$has)){
  $db->prepare("INSERT INTO posts(user_id,body,media_url,media_type) VALUES(?,?,?,?)")->execute([$m,$body,$has?$mu:null,$has?$mt:null]);
}elseif($a==='delpost'){
  $db->prepare("DELETE FROM posts WHERE id=? AND user_id=?")->execute([$id,$m]); // só apaga se for teu
}elseif($a==='comment'&&$body!==''&&mb_strlen($body)<=300){
  $ok=$db->prepare("SELECT 1 FROM posts WHERE id=?"); $ok->execute([$id]);
  if($ok->fetch())$db->prepare("INSERT INTO comments(post_id,user_id,body) VALUES(?,?,?)")->execute([$id,$m,$body]);
}elseif($a==='like'){
  $d=$db->prepare("DELETE FROM likes WHERE user_id=? AND post_id=?"); $d->execute([$m,$id]);
  if(!$d->rowCount()){$ok=$db->prepare("SELECT 1 FROM posts WHERE id=?");$ok->execute([$id]);
    if($ok->fetch())$db->prepare("INSERT INTO likes(user_id,post_id) VALUES(?,?)")->execute([$m,$id]);}
}elseif($a==='follow'&&$id!==$m){
  $d=$db->prepare("DELETE FROM follows WHERE follower_id=? AND followed_id=?"); $d->execute([$m,$id]);
  if(!$d->rowCount()){$ok=$db->prepare("SELECT 1 FROM users WHERE id=?");$ok->execute([$id]);
    if($ok->fetch())$db->prepare("INSERT INTO follows(follower_id,followed_id) VALUES(?,?)")->execute([$m,$id]);}
}elseif($a==='profile'){
  $bio=trim($_POST['bio']??'');
  if(mb_strlen($bio)<=160){
    $set=['bio=?']; $v=[$bio];
    $av=$_POST['avatar_url']??''; if($av!==''&&media_ok($av,'image',true)){$set[]='avatar=?';$v[]=$av;}
    $cv=$_POST['cover_url']??'';  if($cv!==''&&media_ok($cv,'image',true)){$set[]='cover=?';$v[]=$cv;}
    $v[]=$m; $db->prepare("UPDATE users SET ".implode(',',$set)." WHERE id=?")->execute($v);
  }
  $back='profile.php?u='.$_SESSION['uname'];
}
header('Location: '.$back);
