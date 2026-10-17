<?php
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])&&$_SERVER['HTTP_X_FORWARDED_PROTO']==='https']);
session_start();
$h=getenv('MYSQLHOST')?:'localhost'; $p=getenv('MYSQLPORT')?:'3306';
$n=getenv('MYSQLDATABASE')?:'rede'; $u=getenv('MYSQLUSER')?:'root'; $w=getenv('MYSQLPASSWORD')?:'';
try {
  $db=new PDO("mysql:host=$h;port=$p;dbname=$n;charset=utf8mb4",$u,$w,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch (Exception $e) { http_response_code(500); exit('Erro: '.$e->getMessage()); }
if(!$db->query("SHOW TABLES LIKE 'users'")->fetch()){
  foreach([
   "CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(20) UNIQUE NOT NULL, pass VARCHAR(255) NOT NULL, created TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
   "CREATE TABLE posts(id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, body VARCHAR(500) NOT NULL, created TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
   "CREATE TABLE likes(user_id INT NOT NULL, post_id INT NOT NULL, PRIMARY KEY(user_id,post_id), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE)",
   "CREATE TABLE comments(id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, user_id INT NOT NULL, body VARCHAR(300) NOT NULL, created TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
   "CREATE TABLE follows(follower_id INT NOT NULL, followed_id INT NOT NULL, PRIMARY KEY(follower_id,followed_id), FOREIGN KEY(follower_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(followed_id) REFERENCES users(id) ON DELETE CASCADE)"
  ] as $q) $db->exec($q);
}
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function me(){return $_SESSION['uid']??null;}
function need_login(){if(!me()){header('Location: login.php');exit;}}
function csrf(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(16));return $_SESSION['csrf'];}
function csrf_field(){return '<input type="hidden" name="csrf" value="'.csrf().'">';}
function head($t='Rede'){?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($t)?></title><link rel="stylesheet" href="style.css"></head><body>
<nav><a href="index.php"><b>Rede</b></a><span><?php if(me()):?><a href="profile.php?u=<?=e($_SESSION['uname'])?>">@<?=e($_SESSION['uname'])?></a> <form method="post" action="logout.php" style="display:inline"><?=csrf_field()?><button class="link">Sair</button></form><?php endif;?></span></nav><main><?php }
function foot(){echo '</main></body></html>';}
function render_post($p,$back){
  global $db;
  $c=$db->prepare("SELECT c.body,u.username FROM comments c JOIN users u ON u.id=c.user_id WHERE c.post_id=? ORDER BY c.id LIMIT 20");
  $c->execute([$p['id']]); ?>
  <article class="card">
    <a href="profile.php?u=<?=e($p['username'])?>"><b>@<?=e($p['username'])?></b></a> <small><?=e($p['created'])?></small>
    <p><?=nl2br(e($p['body']))?></p>
    <form method="post" action="action.php" class="row"><?=csrf_field()?><input type="hidden" name="a" value="like"><input type="hidden" name="id" value="<?=(int)$p['id']?>"><input type="hidden" name="back" value="<?=e($back)?>">
      <button class="link"><?=$p['liked']?'♥':'♡'?> <?=(int)$p['likes']?></button></form>
    <?php foreach($c as $x):?><div class="cm"><b>@<?=e($x['username'])?></b> <?=e($x['body'])?></div><?php endforeach;?>
    <form method="post" action="action.php" class="row"><?=csrf_field()?><input type="hidden" name="a" value="comment"><input type="hidden" name="id" value="<?=(int)$p['id']?>"><input type="hidden" name="back" value="<?=e($back)?>">
      <input name="body" maxlength="300" placeholder="Comentar..." required><button>Enviar</button></form>
  </article><?php }
