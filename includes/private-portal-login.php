<?php
require_once __DIR__.'/config.php';
if (!isset($portalRole) || !in_array($portalRole,['admin','staff'],true)) { http_response_code(404); exit('Not found.'); }
$portalTitle = $portalRole === 'admin' ? 'Administration Login' : 'Staff Workspace Login';
$error=''; $notice='';
if(user() && (user()['role']??'')===$portalRole){
 redirect($portalRole==='admin'?'admin/index.php':'staff/index.php');
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();
 $attemptKey='portal_attempts_'.$portalRole;
 $attempts=$_SESSION[$attemptKey]??['count'=>0,'start'=>time()];
 if(time()-(int)$attempts['start']>900)$attempts=['count'=>0,'start'=>time()];
 if((int)$attempts['count']>=6){
  $error='Too many attempts. Wait 15 minutes and try again.';
 }else{
  $email=strtolower(trim((string)($_POST['email']??'')));$password=(string)($_POST['password']??'');
  $st=db()->prepare('SELECT * FROM users WHERE email=? AND role=? AND status=1 LIMIT 1');
  $st->execute([$email,$portalRole]);$account=$st->fetch();
  if($account && password_verify($password,$account['password'])){
   unset($_SESSION[$attemptKey]);session_regenerate_id(true);unset($account['password']);$_SESSION['user']=$account;$_SESSION['csrf']=bin2hex(random_bytes(32));
   flash('success','Signed in successfully.');redirect($portalRole==='admin'?'admin/index.php':'staff/index.php');
  }
  $attempts['count']=(int)$attempts['count']+1;$_SESSION[$attemptKey]=$attempts;
  $error='Invalid credentials or this account is not active for this portal.';
 }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=e($portalTitle)?> | ShopNex</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"><style>body{min-height:100vh;background:radial-gradient(circle at top right,#e7d8ff,transparent 38%),linear-gradient(135deg,#f8f5ff,#f2f5ff);display:grid;place-items:center;padding:24px}.portal-card{width:min(100%,460px);border:1px solid #ebe4fb;border-radius:24px;background:#fff;padding:clamp(24px,5vw,42px);box-shadow:0 20px 60px #28115b16}.portal-mark{width:58px;height:58px;border-radius:18px;display:grid;place-items:center;color:white;background:linear-gradient(135deg,#702cff,#ff3e8a);font-size:26px}.btn-portal{background:linear-gradient(100deg,#702cff,#8b38f5);border:0;color:white;font-weight:700;padding:12px;border-radius:12px}.btn-portal:hover{color:white;filter:brightness(.96)}.form-control{padding:12px;border-radius:11px}</style></head><body><main class="portal-card"><div class="portal-mark mb-4"><i class="bi bi-shield-lock-fill"></i></div><div class="text-uppercase small fw-bold text-secondary mb-2">Private ShopNex workspace</div><h1 class="h3 fw-bold mb-2"><?=e($portalTitle)?></h1><p class="text-secondary mb-4"><?= $portalRole==='admin'?'Authorized owner/administrator accounts only.':'Authorized staff accounts only.' ?></p><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><form method="post" autocomplete="on"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label class="form-label fw-semibold" for="email">Email address</label><input id="email" class="form-control mb-3" type="email" name="email" autocomplete="username" required maxlength="190"><label class="form-label fw-semibold" for="password">Password</label><input id="password" class="form-control mb-3" type="password" name="password" autocomplete="current-password" required><button class="btn btn-portal w-100"><?=e($portalRole==='admin'?'Sign in to administration':'Sign in to staff workspace')?></button></form><div class="d-flex justify-content-between mt-3 small"><a href="<?=e(url('forgot-password.php'))?>">Forgot password?</a><a href="<?=e(url('login.php'))?>">Customer site</a></div><hr><p class="small text-secondary mb-0"><i class="bi bi-lock-fill me-1"></i>Role-checked login. Knowing this URL alone does not grant access.</p></main></body></html>
