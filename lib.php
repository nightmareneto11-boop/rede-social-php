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
/* ---- Fase 1: migração (corre uma só vez; segura para bases já existentes) ---- */
$s=$db->query("SHOW COLUMNS FROM users LIKE 'bio'");
if(!$s->fetch()){
  foreach([
   "ALTER TABLE users ADD COLUMN avatar VARCHAR(500) NULL, ADD COLUMN cover VARCHAR(500) NULL, ADD COLUMN bio VARCHAR(160) NOT NULL DEFAULT ''",
   "ALTER TABLE posts ADD COLUMN media_url VARCHAR(500) NULL, ADD COLUMN media_type VARCHAR(10) NULL"
  ] as $q){ try{$db->exec($q);}catch(Exception $e){} } // ignora "coluna já existe" se dois pedidos migrarem ao mesmo tempo
}
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function me(){return $_SESSION['uid']??null;}
function need_login(){if(!me()){header('Location: login.php');exit;}}
function csrf(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(16));return $_SESSION['csrf'];}
function csrf_field(){return '<input type="hidden" name="csrf" value="'.csrf().'">';}

/* ---- Cloudinary (as chaves vêm das Variables do Railway, nunca do código) ---- */
function cloud_name(){return getenv('CLOUDINARY_CLOUD_NAME')?:'';}
function cloud_ready(){return cloud_name()!==''&&getenv('CLOUDINARY_API_KEY')&&getenv('CLOUDINARY_API_SECRET');}
// Só aceita links que vêm mesmo da NOSSA conta Cloudinary (impede links externos/maliciosos)
function media_ok($url,$type,$imageOnly=false){
  $c=cloud_name();
  if($c===''||!is_string($url)||!is_string($type)||strlen($url)>500)return false;
  if(!in_array($type,$imageOnly?['image']:['image','video'],true))return false;
  if(strpos($url,"https://res.cloudinary.com/$c/$type/upload/")!==0)return false;
  return !str_contains($url,'..')&&preg_match('#^[A-Za-z0-9/_.,:%-]+$#',$url)===1;
}
// Pede ao Cloudinary uma versão otimizada (tamanho menor, formato certo)
function cdn($url,$t){return preg_replace('#/upload/#','/upload/'.$t.'/',$url,1);}
function avatar_html($name,$url,$size=40){
  $s=(int)$size;
  if($url&&media_ok($url,'image',true))
    return '<img class="av" width="'.$s.'" height="'.$s.'" alt="" src="'.e(cdn($url,'w_'.($s*2).',h_'.($s*2).',c_fill,g_face,q_auto,f_auto')).'">';
  return '<span class="av av0" style="width:'.$s.'px;height:'.$s.'px;font-size:'.round($s*.45).'px">'.e(mb_strtoupper(mb_substr((string)$name,0,1))).'</span>';
}
function render_media($p){
  $u=$p['media_url']??null;$t=$p['media_type']??null;
  if(!$u||!media_ok($u,(string)$t))return '';
  if($t==='video')return '<video class="media" controls preload="metadata" playsinline src="'.e(cdn($u,'q_auto')).'"></video>';
  return '<img class="media" loading="lazy" alt="" src="'.e(cdn($u,'w_900,q_auto,f_auto')).'">';
}
// Campo de envio de ficheiro (foto/vídeo). $kind: 'any' = foto ou vídeo, 'image' = só foto
function uploader($label,$urlName,$typeName,$kind='any'){
  if(!cloud_ready())return '<small class="muted">Envio de fotos/vídeos ainda não configurado.</small>';
  $acc=$kind==='image'?'image/*':'image/*,video/*';
  return '<div class="up"><label class="upbtn">'.e($label).'<input type="file" hidden accept="'.$acc.'" data-kind="'.e($kind).'" data-url="'.e($urlName).'" data-type="'.e($typeName).'"></label>'
    .'<input type="hidden" name="'.e($urlName).'">'.($typeName?'<input type="hidden" name="'.e($typeName).'">':'')
    .'<span class="ustatus"></span><div class="upreview"></div></div>';
}

function head($t='Rede'){?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf" content="<?=e(csrf())?>"><title><?=e($t)?></title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="social.css"><script src="upload.js" defer></script></head><body>
<nav><a href="index.php"><b>Rede</b></a><span><?php if(me()):?><a href="profile.php?u=<?=e($_SESSION['uname'])?>">@<?=e($_SESSION['uname'])?></a> <form method="post" action="logout.php" style="display:inline"><?=csrf_field()?><button class="link">Sair</button></form><?php endif;?></span></nav><main><?php }
function foot(){echo '</main></body></html>';}
function render_post($p,$back){
  global $db;
  $c=$db->prepare("SELECT c.body,u.username FROM comments c JOIN users u ON u.id=c.user_id WHERE c.post_id=? ORDER BY c.id LIMIT 20");
  $c->execute([$p['id']]); ?>
  <article class="card">
    <div class="ph">
      <a href="profile.php?u=<?=e($p['username'])?>"><?=avatar_html($p['username'],$p['avatar']??null,40)?></a>
      <div><a href="profile.php?u=<?=e($p['username'])?>"><b>@<?=e($p['username'])?></b></a><br><small><?=e($p['created'])?></small></div>
      <?php if((int)$p['user_id']===(int)me()):?><form method="post" action="action.php" class="del" onsubmit="return confirm('Apagar esta publicação?')"><?=csrf_field()?><input type="hidden" name="a" value="delpost"><input type="hidden" name="id" value="<?=(int)$p['id']?>"><input type="hidden" name="back" value="<?=e($back)?>"><button class="link">Apagar</button></form><?php endif;?>
    </div>
    <?php if($p['body']!==''):?><p><?=nl2br(e($p['body']))?></p><?php endif;?>
    <?=render_media($p)?>
    <form method="post" action="action.php" class="row"><?=csrf_field()?><input type="hidden" name="a" value="like"><input type="hidden" name="id" value="<?=(int)$p['id']?>"><input type="hidden" name="back" value="<?=e($back)?>">
      <button class="link"><?=$p['liked']?'♥':'♡'?> <?=(int)$p['likes']?></button></form>
    <?php foreach($c as $x):?><div class="cm"><b>@<?=e($x['username'])?></b> <?=e($x['body'])?></div><?php endforeach;?>
    <form method="post" action="action.php" class="row"><?=csrf_field()?><input type="hidden" name="a" value="comment"><input type="hidden" name="id" value="<?=(int)$p['id']?>"><input type="hidden" name="back" value="<?=e($back)?>">
      <input name="body" maxlength="300" placeholder="Comentar..." required><button>Enviar</button></form>
  </article><?php }
