<?php require 'lib.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $un=trim($_POST['username']??''); $pw=$_POST['password']??'';
  if(!hash_equals(csrf(),$_POST['csrf']??''))$err='Sessão inválida.';
  elseif(!preg_match('/^[A-Za-z0-9_]{3,20}$/',$un))$err='Usuário: 3 a 20 letras, números ou _.';
  elseif(strlen($pw)<8)$err='Senha com no mínimo 8 caracteres.';
  else{
    try{
      $db->prepare("INSERT INTO users(username,pass) VALUES(?,?)")->execute([$un,password_hash($pw,PASSWORD_DEFAULT)]);
      session_regenerate_id(true); $_SESSION['uid']=(int)$db->lastInsertId(); $_SESSION['uname']=$un;
      header('Location: index.php'); exit;
    }catch(PDOException $x){$err='Esse usuário já existe.';}
  }
}
head('Criar conta'); ?>
<h2>Criar conta</h2><?php if($err):?><p class="err"><?=e($err)?></p><?php endif;?>
<form method="post" class="card"><?=csrf_field()?><input name="username" placeholder="usuário" required><input name="password" type="password" placeholder="senha (8+)" required><button>Cadastrar</button></form>
<p>Já tem conta? <a href="login.php">Entrar</a></p>
<?php foot();
