<?php require 'lib.php'; need_login(); $m=me();
$un=$_GET['u']??''; $s=$db->prepare("SELECT id,username,created FROM users WHERE username=?"); $s->execute([$un]); $u=$s->fetch();
if(!$u){http_response_code(404);head('Não encontrado');echo '<p>Usuário não encontrado.</p>';foot();exit;}
$f=$db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id=? AND followed_id=?"); $f->execute([$m,$u['id']]); $seguindo=(bool)$f->fetchColumn();
$n1=$db->prepare("SELECT COUNT(*) FROM follows WHERE followed_id=?"); $n1->execute([$u['id']]);
$n2=$db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id=?"); $n2->execute([$u['id']]);
$s=$db->prepare("SELECT p.*,u.username,(SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) likes,(SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id AND l.user_id=?) liked FROM posts p JOIN users u ON u.id=p.user_id WHERE p.user_id=? ORDER BY p.id DESC LIMIT 50");
$s->execute([$m,$u['id']]); $back='profile.php?u='.$u['username'];
head('@'.$u['username']); ?>
<div class="card"><h2>@<?=e($u['username'])?></h2><small><?=(int)$n1->fetchColumn()?> seguidores · <?=(int)$n2->fetchColumn()?> seguindo</small>
<?php if($u['id']!=$m):?><form method="post" action="action.php"><?=csrf_field()?><input type="hidden" name="a" value="follow"><input type="hidden" name="id" value="<?=(int)$u['id']?>"><input type="hidden" name="back" value="<?=e($back)?>"><button><?=$seguindo?'Deixar de seguir':'Seguir'?></button></form><?php endif;?></div>
<?php foreach($s as $p) render_post($p,$back); foot();
