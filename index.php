<?php require 'lib.php'; need_login(); $m=me();
$s=$db->prepare("SELECT p.*,u.username,(SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) likes,(SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id AND l.user_id=?) liked FROM posts p JOIN users u ON u.id=p.user_id WHERE p.user_id=? OR p.user_id IN (SELECT followed_id FROM follows WHERE follower_id=?) ORDER BY p.id DESC LIMIT 50");
$s->execute([$m,$m,$m]); $posts=$s->fetchAll();
$q=$db->prepare("SELECT username FROM users WHERE id<>? AND id NOT IN (SELECT followed_id FROM follows WHERE follower_id=?) ORDER BY id DESC LIMIT 8"); $q->execute([$m,$m]); $sug=$q->fetchAll();
head('Feed'); ?>
<form method="post" action="action.php" class="card"><?=csrf_field()?><input type="hidden" name="a" value="post"><input type="hidden" name="back" value="index.php">
<textarea name="body" maxlength="500" placeholder="O que está acontecendo?" required></textarea><button>Publicar</button></form>
<?php if($sug):?><div class="card"><b>Sugestões:</b> <?php foreach($sug as $x):?><a href="profile.php?u=<?=e($x['username'])?>">@<?=e($x['username'])?></a> <?php endforeach;?></div><?php endif;?>
<?php foreach($posts as $p) render_post($p,'index.php'); if(!$posts):?><p>Nada por aqui ainda. Publique ou siga alguém.</p><?php endif;
foot();
