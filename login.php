<?php require 'lib.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals(csrf(),$_POST['csrf']??''))$err='Sessão inválida.';
  else{
    $s=$db->prepare("SELECT * FROM users WHERE username=?"); $s->execute([trim($_POST['username']??'')]); $r=$s->fetch();
    if($r&&password_verify($_POST['password']??'',$r['pass'])){
      session_regenerate_id(true); $_SESSION['uid']=(int)$r['id']; $_SESSION['uname']=$r['username'];
      header('Location: index.php'); exit;
    } $err='Usuário ou senha incorretos.';
  }
}
head('Entrar'); ?>
<h2>Entrar</h2><?php if($err):?><p class="err"><?=e($err)?></p><?php endif;?>
<form method="post" class="card"><?=csrf_field()?><input name="username" placeholder="usuário" required><input name="password" type="password" placeholder="senha" required><button>Entrar</button></form>
<p>Novo aqui? <a href="register.php">Criar conta</a></p>
<?php foot();
