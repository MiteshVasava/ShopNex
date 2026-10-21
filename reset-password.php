<?php
require_once 'includes/config.php';
$title='Reset Password';$error='';$valid=false;$token=(string)($_GET['token']??$_POST['token']??'');
if(preg_match('/^[a-f0-9]{64}$/',$token)){
 try{$pdo=db();$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,token_hash CHAR(64) NOT NULL UNIQUE,expires_at DATETIME NOT NULL,used_at DATETIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(user_id),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");$st=$pdo->prepare('SELECT id,user_id FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1');$st->execute([hash('sha256',$token)]);$resetRow=$st->fetch();$valid=(bool)$resetRow;}catch(Throwable $e){$valid=false;}
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$password=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm']??'');
 if(!$valid)$error='This reset link is invalid or expired. Request a new one.';
 elseif(strlen($password)<8)$error='Use at least 8 characters for your new password.';
 elseif(!hash_equals($password,$confirm))$error='Passwords do not match.';
 else{try{$pdo->beginTransaction();$st=$pdo->prepare('SELECT user_id FROM password_resets WHERE id=? AND used_at IS NULL AND expires_at>NOW() FOR UPDATE');$st->execute([(int)$resetRow['id']]);$row=$st->fetch();if(!$row)throw new RuntimeException('Expired');$pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),(int)$row['user_id']]);$pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=?')->execute([(int)$resetRow['id']]);$pdo->commit();flash('success','Password reset. You can now sign in.');redirect('login.php');}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error='Could not reset the password. Request a new link.';}}
}
require 'includes/header.php';
?>
<div class="row justify-content-center py-5"><div class="col-md-7 col-lg-5"><div class="bg-white border rounded-4 p-4 p-lg-5 shadow-sm"><h1 class="h3 fw-bold">Set a new password</h1><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><?php if(!$valid):?><p class="text-secondary">This reset link is invalid or expired. <a href="forgot-password.php">Request another link</a>.</p><?php else:?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="token" value="<?=e($token)?>"><label class="form-label">New password</label><input class="form-control mb-3" type="password" name="password" minlength="8" autocomplete="new-password" required><label class="form-label">Confirm password</label><input class="form-control mb-3" type="password" name="confirm" minlength="8" autocomplete="new-password" required><button class="btn btn-purple w-100">Reset password</button></form><?php endif;?></div></div></div>
<?php require 'includes/footer.php'; ?>