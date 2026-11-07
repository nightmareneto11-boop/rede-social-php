<?php require 'lib.php'; need_login(); $m=me();
$un=$_GET['u']??''; $s=$db->prepare("SELECT id,username,created,avatar,cover,bio FROM users WHERE username=?"); $s->execute([$un]); $u=$s->fetch();
if(!$u){http_response_code(404);head('Não encontrado');echo '<p>Usuário não encontrado.</p>';foot();exit;}
$f=$db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id=? AND followed_id=?"); $f->execute([$m,$u['id']]); $seguindo=(bool)$f->fetchColumn();
$n1=$db->prepare("SELECT COUNT(*) FROM follows WHERE followed_id=?"); $n1->execute([$u['id']]);
$n2=$db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id=?"); $n2->execute([$u['id']]);
$n3=$db->prepare("SELECT COUNT(*) FROM posts WHERE user_id=?"); $n3->execute([$u['id']]);
$s=$db->prepare("SELECT p.*,u.username,u.avatar,(SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) likes,(SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id AND l.user_id=?) liked FROM posts p JOIN users u ON u.id=p.user_id WHERE p.user_id=? ORDER BY p.id DESC LIMIT 50");
$s->execute([$m,$u['id']]); $back='profile.php?u='.$u['username']; $mine=((int)$u['id']===(int)$m);
head('@'.$u['username']); ?>
<div class="card profile">
  <?php if($u['cover']&&media_ok($u['cover'],'image',true)):?><img class="cover" alt="" src="<?=e(cdn($u['cover'],'w_1200,h_400,c_fill,q_auto,f_auto'))?>"><?php else:?><div class="cover cover0"></div><?php endif;?>
  <div class="pinfo">
    <div class="pav"><?=avatar_html($u['username'],$u['avatar'],96)?></div>
    <h2>@<?=e($u['username'])?></h2>
    <?php if($u['bio']!==''):?><p class="bio"><?=nl2br(e($u['bio']))?></p><?php endif;?>
    <small><?=(int)$n3->fetchColumn()?> publicações · <?=(int)$n1->fetchColumn()?> seguidores · <?=(int)$n2->fetchColumn()?> seguindo</small>
    <?php if(!$mine):?><form method="post" action="action.php"><?=csrf_field()?><input type="hidden" name="a" value="follow"><input type="hidden" name="id" value="<?=(int)$u['id']?>"><input type="hidden" name="back" value="<?=e($back)?>"><button><?=$seguindo?'Deixar de seguir':'Seguir'?></button></form><?php endif;?>
  </div>
</div>
<?php if($mine):?>
<details class="card"><summary><b>Editar perfil</b></summary>
  <form method="post" action="action.php"><?=csrf_field()?><input type="hidden" name="a" value="profile">
    <label>Sobre ti <small class="muted">(até 160 caracteres)</small></label>
    <textarea name="bio" maxlength="160" placeholder="Escreve algo sobre ti..."><?=e($u['bio'])?></textarea>
    <div><?=uploader('🖼️ Mudar foto de perfil','avatar_url','','image')?></div>
    <div><?=uploader('🌄 Mudar foto de capa','cover_url','','image')?></div>
    <button>Guardar</button>
  </form>
</details>
<?php endif;?>
<?php foreach($s as $p) render_post($p,$back); foot();
