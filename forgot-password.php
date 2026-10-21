<?php
require_once 'includes/config.php';
$title='Forgot Password'; $message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf(); $email=strtolower(trim((string)($_POST['email']??'')));
 // Generic response avoids exposing whether an account exists.
 $generic='If this email belongs to an active account, a password-reset link will be sent if email delivery is configured.';
 if(filter_var($email,FILTER_VALIDATE_EMAIL)){
  try{
   $pdo=db();
   $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,token_hash CHAR(64) NOT NULL UNIQUE,expires_at DATETIME NOT NULL,used_at DATETIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(user_id),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
   $st=$pdo->prepare("SELECT id,email,name FROM users WHERE email=? AND status=1 LIMIT 1");$st->execute([$email]);$account=$st->fetch();
   if($account){
    $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);$expires=date('Y-m-d H:i:s',time()+3600);
    $pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([(int)$account['id']]);
    $pdo->prepare('INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,?)')->execute([(int)$account['id'],$hash,$expires]);
    $link=url('reset-password.php?token='.urlencode($token));
    $from=site_setting('mail_from','');
    $subject='Reset your ShopNex password';
    $body="Hello ".$account['name'].",\n\nA password reset was requested for your ShopNex account. Open this link within 60 minutes:\n".$link."\n\nIf you did not request this, ignore this email. Your password will not change unless the link is used.\n";
    $headers="MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    if($from!==''&&filter_var($from,FILTER_VALIDATE_EMAIL))$headers.="From: ShopNex <".$from.">\r\n";
    // mail() requires a configured local/SMTP mail transport.
    @mail($account['email'],$subject,$body,$headers);
   }
  }catch(Throwable $e){error_log('ShopNex password reset setup/delivery error: '.$e->getMessage());}
 }
 $message=$generic;
}
require 'includes/header.php';
?>
<div class="row justify-content-center py-5"><div class="col-md-7 col-lg-5"><div class="bg-white border rounded-4 p-4 p-lg-5 shadow-sm"><div class="rounded-3 p-3 d-inline-block mb-3" style="background:#f0e8ff;color:#702cff"><i class="bi bi-envelope-lock fs-3"></i></div><h1 class="h3 fw-bold">Forgot your password?</h1><p class="text-secondary">Enter your account email. We'll send a one-time reset link if email delivery is configured.</p><?php if($message):?><div class="alert alert-info"><?=e($message)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label class="form-label">Email address</label><input class="form-control mb-3" name="email" type="email" autocomplete="email" required><button class="btn btn-purple w-100">Request reset link</button></form><p class="small text-secondary mt-3 mb-0">For security, ShopNex never emails your existing password. It sends a temporary reset link instead.</p><a class="d-inline-block mt-3" href="login.php">Back to login</a></div></div></div>
<?php require 'includes/footer.php'; ?>