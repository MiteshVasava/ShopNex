<?php
require_once '../includes/config.php';
require_staff_or_admin();
$pdo=db(); $me=user(); $title='My Profile';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf(); $action=(string)($_POST['action']??'');
 if($action==='profile'){
  $name=trim((string)($_POST['name']??'')); $phone=trim((string)($_POST['phone']??''));
  if($name===''||strlen($name)>120){flash('danger','Enter a valid name.');}
  else{$st=$pdo->prepare('UPDATE users SET name=?,phone=? WHERE id=? AND role IN (\'admin\',\'staff\')');$st->execute([$name,$phone,(int)$me['id']]);$_SESSION['user']['name']=$name;flash('success','Profile updated.');}
 } elseif($action==='password'){
  $st=$pdo->prepare('SELECT password FROM users WHERE id=? AND role IN (\'admin\',\'staff\')');$st->execute([(int)$me['id']]);$old=$st->fetchColumn();
  if(!$old||!password_verify((string)($_POST['current']??''),$old))flash('danger','Current password is incorrect.');
  elseif(strlen((string)($_POST['new']??''))<8)flash('danger','New password must contain at least 8 characters.');
  else{$st=$pdo->prepare('UPDATE users SET password=? WHERE id=?');$st->execute([password_hash((string)$_POST['new'],PASSWORD_DEFAULT),(int)$me['id']]);flash('success','Password changed successfully.');}
 }
 redirect(basename(dirname($_SERVER['SCRIPT_NAME']))==='admin'?'profile.php':'profile.php');
}
$st=$pdo->prepare('SELECT name,email,phone,role,created_at FROM users WHERE id=? AND role IN (\'admin\',\'staff\')');$st->execute([(int)$me['id']]);$profile=$st->fetch();
if(!$profile){http_response_code(403);exit('Profile unavailable.');}
require '../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-lg-9"><div class="mb-4"><span class="text-uppercase text-secondary small fw-semibold">Private workspace</span><h1 class="section-title mb-1">My Profile</h1><p class="text-secondary mb-0">Manage your staff/admin account details and password.</p></div><div class="row g-4"><div class="col-md-6"><div class="bg-white border rounded-4 p-4 h-100"><h5 class="mb-3"><i class="bi bi-person-gear text-purple me-2"></i>Profile details</h5><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="profile"><label class="form-label">Full name</label><input class="form-control mb-3" name="name" value="<?=e($profile['name'])?>" maxlength="120" required><label class="form-label">Email address</label><input class="form-control mb-3" value="<?=e($profile['email'])?>" disabled><label class="form-label">Role</label><input class="form-control mb-3" value="<?=e(ucfirst($profile['role']))?>" disabled><label class="form-label">Phone (optional)</label><input class="form-control mb-3" name="phone" value="<?=e($profile['phone']??'')?>"><button class="btn btn-purple">Save profile</button></form></div></div><div class="col-md-6"><div class="bg-white border rounded-4 p-4 h-100"><h5 class="mb-3"><i class="bi bi-shield-lock text-purple me-2"></i>Change password</h5><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="password"><label class="form-label">Current password</label><input class="form-control mb-3" type="password" name="current" autocomplete="current-password" required><label class="form-label">New password</label><input class="form-control mb-3" type="password" name="new" minlength="8" autocomplete="new-password" required><button class="btn btn-outline-purple">Update password</button></form><hr><p class="small text-secondary mb-0">Admin and staff profiles do not include customer shipping addresses or customer-only account controls.</p></div></div></div></div></div>
<?php require '../includes/footer.php'; ?>
