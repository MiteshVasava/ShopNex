<?php
require_once 'includes/config.php';
$wasOperations=SHOPNEX_OPERATIONS_CONTEXT;
$role=user()['role']??null;
$_SESSION=[];
if(ini_get('session.use_cookies')){
 $params=session_get_cookie_params();
 setcookie(session_name(),'',time()-42000,$params['path']?:'/', $params['domain']??'',(bool)($params['secure']??false),(bool)($params['httponly']??true));
}
session_destroy();
if($wasOperations){
 $dest=$role==='staff'?'nex-staff-console-2d8b6e.php':'nex-owner-console-7f3a9c.php';
 header('Location: '.url($dest));
}else header('Location: '.url('login.php'));
exit;
