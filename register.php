<?
/**
 * Register user from invitation
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


require "inc/common.php";

$invite = trim(@$_GET['invite']);

if ($invite) {
	$sql = "SELECT *, now() > invite_expiry AS invite_expired FROM members WHERE invite=".DB::esc($invite)." AND activated IS NULL";
	$result = DB::query($sql);
	$member = DB::fetch_object($result, "Member");
	if ($member) {
		DB::to_bool($member->invite_expired);
		if ($member->invite_expired) {
			warning(sprintf(_("The code you've entered is expired! Please contact %s to get a new one!"), MAIL_SUPPORT), true);
			$member = false;
		}
	} else {
		warning(_("The code you've entered is invalid!"));
	}
} else {
	$member = false;
}

if (!$member) {

	html_head(_("Registration"));

?>
<form action="<?=BN?>" method="GET">

<fieldset class="member">
	<div class="input td0">
			<?=_("Please enter the invite code you've received.")?><br>
		<label for="invite"><?=_("Invite code")?></label>
		<span class="input"><input type="text" name="invite" size="30" value="<?=h($invite)?>"></span>
	</div>
</fieldset>
<br>
<input type="submit" value="<?=_("Proceed with registration")?>">
</form>
<?

	html_foot();
	exit;

}


$username = "";
$password = "";
$mail     = "";

if ($action) {
	switch ($action) {
	case "activate":
		action_required_parameters('username', 'password', 'password2', 'mail');

		$username  = trim($_POST['username']);
		$password  = trim($_POST['password']);
		$password2 = trim($_POST['password2']);
		$mail      = trim($_POST['mail']);

		// username
		if (!$username) {
			warning(_("Please enter a user name!"));
			break;
		}
		if (mb_strlen($username) < 3) {
			warning(_("The user name must have at least 3 characters!"));
			break;
		}

		// password
		if (!$password or !$password2) {
			warning(_("Please enter a password!"));
			break;
		}
		if ($password != $password2) {
			warning(_("The two password fields do not match!"));
			$password = "";
			break;
		}
		if (mb_strlen($password) < 8) {
			warning(_("The password name must have at least 8 characters!"));
			$password = "";
			break;
		}

		// mail
		if (!$mail) {
			warning(_("Please enter a mail address!"));
			break;
		}
		if ( ! filter_var($mail, FILTER_VALIDATE_EMAIL) ) {
			warning(_("This email address is not valid!"));
			break;
		}

		Login::$member = $member;

		Login::$member->set_unique_username($username);
		Login::$member->password = crypt($password);
		if ( ! Login::$member->update(array('username', 'password')) ) break;
		success(_("Your account has been activated."));

		Login::$member->set_mail($mail);

		$_SESSION['member'] = Login::$member->id;

		redirect("member.php");

	default:
		warning(_("Unknown action"));
		redirect();
	}
}


html_head(_("Registration"));

form(h(BN."?invite=".$invite));
?>
<fieldset class="member">
	<div class="description td0"><?=_("Please choose a user name, i.e. your real name or your nick name. This name will be used for login and will be shown to others to identify you. The user name is case sensitive.")?></div>
	<div class="input td0">
		<label for="username"><?=_("Username")?></label>
		<span class="input"><input type="text" name="username" value="<?=h($username)?>" size="25"></span>
	</div>
	<div class="description td1"><?=_("Please choose a password and enter it twice. The password is case sensitive and has to be at least 8 characters long.")?></div>
	<div class="input td1">
		<label for="password"><?=_("Password")?></label>
		<span class="input"><input type="password" name="password" value="<?=h($password)?>" size="25"> <?=_("again")?>: <input type="password" name="password2" value="<?=h($password)?>" size="25"></span>
	</div>
	<div class="description td0"><?=_("Please enter your email address. This address will be used for automatic notifications (if you request them) and in case you've lost your password. This address will not be published. After registration you will receive an email with a confirmation link.")?></div>
	<div class="input td0">
		<label for="mail"><?=_("Mail address for notifications")?></label>
		<span class="input"><input type="text" name="mail" value="<?=h($mail)?>" size="40"></span>
	</div>
</fieldset>

<br>
<input type="hidden" name="action" value="activate">
<input type="submit" value="<?=_("Activate account")?>">
<?
form_end();

html_foot();