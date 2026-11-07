<?php require 'lib.php'; need_login();
header('Content-Type: application/json');
if($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals(csrf(),$_SERVER['HTTP_X_CSRF']??'')){http_response_code(400);exit(json_encode(['error'=>'Pedido inválido.']));}
$c=getenv('CLOUDINARY_CLOUD_NAME');$k=getenv('CLOUDINARY_API_KEY');$sec=getenv('CLOUDINARY_API_SECRET');
if(!$c||!$k||!$sec){http_response_code(503);exit(json_encode(['error'=>'Envio de ficheiros ainda não configurado.']));}
$ts=time();$folder='rede';$fmt='jpg,jpeg,png,gif,webp,mp4,mov,webm';
// Assinatura: parâmetros por ordem alfabética + segredo. O segredo NUNCA sai do servidor.
$sig=sha1("allowed_formats=$fmt&folder=$folder&timestamp=$ts".$sec);
echo json_encode(['cloud_name'=>$c,'api_key'=>$k,'timestamp'=>$ts,'folder'=>$folder,'allowed_formats'=>$fmt,'signature'=>$sig]);
