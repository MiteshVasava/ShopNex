<?php
require_once 'includes/config.php';
if (user() && (user()['role'] ?? '') === 'customer') redirect('index.php');
$title='Customer Login'; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();
 $email=strtolower(trim((string)($_POST['email']??''))); $pass=(string)($_POST['password']??'');
 $st=db()->prepare("SELECT * FROM users WHERE email=? AND status=1 AND role='customer' LIMIT 1");
 $st->execute([$email]); $u=$st->fetch();
 if($u && password_verify($pass,$u['password'])){
  session_regenerate_id(true); unset($u['password']); $_SESSION['user']=$u; $_SESSION['csrf']=bin2hex(random_bytes(32));
  flash('success','Welcome back, '.$u['name'].'!'); redirect('index.php');
 }
 $error='Invalid email or password.';
}
require 'includes/header.php';
?>
<div class="row justify-content-center py-5"><div class="col-md-6 col-lg-5"><div class="bg-white border rounded-4 p-4 p-lg-5 shadow-sm"><div class="rounded-3 p-3 d-inline-block mb-3" style="background:#f0e8ff;color:#702cff"><i class="bi bi-person-circle fs-3"></i></div><h1 class="h3 fw-bold">Customer Login</h1><p class="text-secondary">Sign in to shop, manage your orders, and save addresses.</p><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label class="form-label">Email address</label><input name="email" type="email" class="form-control mb-3" required autocomplete="username"><label class="form-label">Password</label><input name="password" type="password" class="form-control mb-3" required autocomplete="current-password"><button class="btn btn-purple w-100">Sign in</button></form><div class="text-end mt-2"><a class="small" href="forgot-password.php">Forgot password?</a></div><p class="mt-3 mb-0">New customer? <a href="register.php">Create account</a></p></div></div></div>
<?php require 'includes/footer.php'; ?>