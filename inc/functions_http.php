<?
/**
 * functions used on every page, which is called by http
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 * @see inc/common_http.php
 */


/**
 * http redirect
 *
 * @param string  $target (optional) relative or absolute URI
 */
function redirect($target="") {

	if ($target) {
		if (!lefteq($target, "/") and !lefteq($target, "http")) {
			// make relative paths absolute
			$dirname = dirname($_SERVER['PHP_SELF']);
			if ($dirname=="/") $target = "/".$target; else $target = $dirname."/".$target;
		}
	} else {
		// reload the page to get rid of POST data
		$target = $_SERVER['REQUEST_URI'];
	}

	if (DEBUG) {
		// save page infos to show them in the debug output on the next page
		if (!isset($_SESSION['redirects'])) $_SESSION['redirects'] = array();
		$_SESSION['redirects'][] = array(
			'target'      => $target,
			'BN'          => BN,
			'REQUEST_URI' => $_SERVER['REQUEST_URI'],
			'GET'         => $_GET,
			'POST'        => $_POST
		);
	}

	// save not yet displayed output to display it on the next page
	if (isset($_SESSION['output'])) {
		$_SESSION['output'] .= ob_get_clean();
	} else {
		$_SESSION['output'] = ob_get_clean();
	}

	session_write_close(); // release session lock

	header("Location: ".$target);
	exit;
}


/**
 * head part of the page and not yet displayed output
 *
 * @param string  $title
 * @param boolean $help  (optional) display a help block next to <h1>
 */
function html_head($title, $help=false) {

	$output = ob_get_clean();

	// we use HTML 5
?>
<!DOCTYPE html>
<html lang="<?=LANG?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?=h(TITLE." - ".$title)?></title>
	<link rel="stylesheet" media="all" href="css/style.css?<?=version()?>">
<?
	if (Login::$admin) {
?>
	<link rel="stylesheet" media="all" href="css/admin.css?<?=version()?>">
<?
	}
	// generated by http://realfavicongenerator.net/
	// If the installation is in a subfolder, all these icons and favicon.ico have to be either copied to the webroot or deleted from this html head.
?>
	<link rel="apple-touch-icon" sizes="57x57"   href="/apple-touch-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="72x72"   href="/apple-touch-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="60x60"   href="/apple-touch-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="76x76"   href="/apple-touch-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon-180x180.png">
	<link rel="icon" type="image/png" href="/favicon-192x192.png" sizes="192x192">
	<link rel="icon" type="image/png" href="/favicon-160x160.png" sizes="160x160">
	<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
	<link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
	<link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
	<meta name="msapplication-TileColor" content="#603cba">
	<meta name="msapplication-TileImage" content="/mstile-144x144.png">
<?
	// end of favicons
?>
	<!--[if lt IE 9]>
	<script src="js/html5shiv-printshiv.js"></script>
	<![endif]-->
</head>
<body>

<?
	if (DEBUG) {
?>
<!--
<?=strtr( print_r(
				array(
					'BN'          => BN,
					'REQUEST_URI' => $_SERVER['REQUEST_URI'],
					'GET'         => $_GET,
					'POST'        => $_POST,
					'SESSION'     => $_SESSION
				),
				true ), array("<!--"=>"<!-", "-->"=>"->") );
		unset($_SESSION['redirects']);
?>
-->
<?
	}
?>

<header>
	<a href="index.php" id="logo"><img src="img/logo.png" width="58" height="50" alt="<?=_("Logo")?>"></a>
	<div id="header">
		<div id="user">
			<? html_user(); ?>
		</div>
		<nav>
			<ul>
				<li><a href="index.php" id="home">Basisentscheid</a></li>
				<li>
					<form method="GET" action="<?
	switch (BN) {
		// jump to different page if the same page doesn't show the equivalent content in other groups
	case "proposal.php":
	case "proposal_edit.php":
	case "draft.php":
		echo "proposals.php";
		$hidden = false;
		break;
	default:
		echo BN;
	case "periods.php":
	case "admin_areas.php":
		$hidden = array(
			'ngroup' => null, // remove ngroup, because the new ngroup comes from the drop down menu
			'id'     => null  // remove id to go back from edit to list mode
		);
	}
	?>">
						<select name="ngroup" onchange="this.form.submit()">
<?
	if (Login::$member) {
		$sql = "SELECT ngroup.*, member FROM ngroup
			LEFT JOIN member_ngroup ON member_ngroup.ngroup = ngroup.id AND member_ngroup.member = ".intval(Login::$member->id);
	} else {
		$sql = "SELECT * FROM ngroup";
	}
	$sql .= " ORDER BY name";
	$result = DB::query($sql);
	$ngroups = array();
	while ( $ngroup = DB::fetch_object($result, "Ngroup") ) {
		$ngroups[] = $ngroup;
	}
	$ngroups = Ngroup::parent_sort_active($ngroups);
	// own ngroups
	if (Login::$member) {
?>
							<optgroup label="<?=_("own groups")?>">
<?
		foreach ($ngroups as $ngroup) {
			if (!$ngroup->member) continue;
			// save groups list of the logged in member
			Login::$ngroups[] = $ngroup->id;
			// use the first ngroup as default
			if ($_SESSION['ngroup']==0) $_SESSION['ngroup'] = $ngroup->id;
?>
								<option value="<?=$ngroup->id?>"<?
			if ($ngroup->id==$_SESSION['ngroup']) { ?> selected class="selected"<? }
			?>>&#9670; <?=$ngroup->name?></option>
<?
		}
?>
							</optgroup>
<?
	}
	// other ngroups
?>
							<optgroup label="<?=Login::$member?_("other groups"):_("groups")?>">
<?
	foreach ($ngroups as $ngroup) {
		// save list of active groups
		Ngroup::$active_ngroups[] = $ngroup->id;
		if (Login::$member and $ngroup->member) continue;
		// use the first ngroup as default
		if ($_SESSION['ngroup']==0) $_SESSION['ngroup'] = $ngroup->id;
?>
								<option value="<?=$ngroup->id?>"<?
		if ($ngroup->id==$_SESSION['ngroup']) { ?> selected class="selected"<? }
		?>>&#9671; <?=$ngroup->name?></option>
<?
	}
?>
							</optgroup>
						</select>
<?
	// add the hidden fields after the drop down menu to have ngroup always in the first place of the GET parameters
	if ($hidden) URI::hidden($hidden);
?>
					</form>
				</li>
<?
	navlink('proposals.php', _("Proposals"), true);
	navlink('periods.php', _("Periods"), true);
	if (Login::$admin) {
		navlink('admin_areas.php', _("Areas"), true);
	} else {
		navlink('areas.php', _("Areas"), true);
	}
?>
			</ul>
			<ul class="manual">
<?
	navlink('manual.php', _("Manual"));
?>
			</ul>
<?
	if (Login::$admin) {
?>
			<ul class="admin">
<?
		navlink('admin_members.php', _("Members"));
		navlink('admins.php', _("Admins"));
		navlink('admin_ngroups.php', _("Groups"));
?>
			</ul>
<?
	}
?>
		</nav>
		<div class="rclear"></div>
	</div>
</header>

<h1><?=$title?></h1>
<?
	if ($help) help("", true);
?>
<div class="clearfix"></div>
<?

	// show MOTD once for each session and always on the home page
	if (
		defined("MOTD") and
		// display it always on the home page
		(empty($_SESSION['motd_seen']) or BN=="index.php") and
		// never display it on these pages
		BN != "register.php" and
		BN != "reset_password.php" and
		BN != "request_password_reset.php" and
		BN != "confirm_mail.php"
	) {
?>
<section class="motd"><?=MOTD?></section>
<?
		$_SESSION['motd_seen'] = true;
	}

	// not yet displayed output from previous page with redirect
	if (isset($_SESSION['output'])) {
		if ($_SESSION['output']) {
?>
<section class="messages"><?=$_SESSION['output']?></section>
<div class="clearfix"></div>
<?
		}
		unset($_SESSION['output']);
	}

	session_write_close(); // release session lock

	// output from before the html head
	if ($output) {
?>
<section class="messages"><?=$output?></section>
<div class="clearfix"></div>
<?
	}

	$GLOBALS['html_head_issued'] = true;
}


/**
 * one navigation link
 *
 * @param string  $file
 * @param string  $title
 * @param boolean $add_ngroup (optional)
 */
function navlink($file, $title, $add_ngroup=false) {
?>
				<li><a href="<?=$file;
	if ($add_ngroup) { ?>?ngroup=<?=$_SESSION['ngroup']; }
	?>"<? if (BN==$file) { ?> class="active"<? } ?>><?=$title?></a></li>
<?
}


/**
 * display the user/login area
 */
function html_user() {
	if (Login::$member or Login::$admin) {
		if (Login::$member) {
			printf(
				_("logged in as %s"),
				'<a href="settings.php" class="user">'.Login::$member->username().'</a>'
			);
			?>&nbsp;<?
			if (Login::$member->eligible) {
?>
<img src="img/eligible.png" width="11" height="16" alt="<?=_("eligible")?>" title="<?=_("You are eligible.")?>">
<?
			} else {
?>
<img src="img/not_eligible.png" width="11" height="16" alt="<?=_("not eligible")?>" title="<?=_("You are not eligible.")?>">
<?
			}
			if (Login::$member->verified) {
?>
<img src="img/verified.png" width="16" height="12" alt="<?=_("verified")?>" title="<?=_("You are verified.")?>">
<?
			} else {
?>
<img src="img/not_verified.png" width="16" height="12" alt="<?=_("not verified")?>" title="<?=_("You are not verified.")?>">
<?
			}
		} else {
			printf(
				_("logged in as admin %s"),
				'<span class="admin">'.Login::$admin->username.'</span>'
			);
		}
		form("", 'class="logout"');
?>
<input type="hidden" name="action" value="logout">
<input type="submit" value="<?=_("Log out")?>">
<?
		form_end();
	} else {
		form("login.php", 'class="login"');
?>
<input type="hidden" name="origin" value="<?=URI::same()?>">
<input type="submit" value="<?=_("Log in")?>">
<?
		form_end();
	}
}


/**
 * navigation for member settings
 */
function display_nav_settings() {
?>
<div class="filter nav">
	<a href="settings.php"<?
	if (BN=="settings.php") { ?> class="active"<? }
	?>><?=_("Account")?></a>
	<a href="settings_notifications.php"<?
	if (BN=="settings_notifications.php") { ?> class="active"<? }
	?>><?=_("Email notifications")?></a>
<? if (GNUPG_SIGN_KEY) { ?>
	<a href="settings_encryption.php"<?
		if (BN=="settings_encryption.php") { ?> class="active"<? }
		?>><?=_("Email encryption")?></a>
<? } ?>
</div>
<?
	help();
}


/**
 * foot part of the page
 */
function html_foot() {
?>

<footer><a href="about.php"><?=_("About")?></a></footer>
<?
	if (isset($GLOBALS['js_open_link'])) {
?>
<script>
function openLink(href) {
	var forms = document.getElementsByTagName('form');
	for (var i = 0; i < forms.length; i++) {
		if (forms[i].classList.contains('check_unload') && formIsDirty(forms[i])) {
			if (confirm('<?=_("You have unsaved changes. Do you want to open the link in a new window?")?>')) {
				window.open(href, '_blank');
				return false;
			}
		}
	}
	return true;
}
var elements = document.getElementsByTagName('a');
for (var i = 0; i < elements.length; i++) {
	elements[i].onclick = function () {
		return openLink(this.href);
	}
}
</script>
<?
	}
?>
</body>
</html>
<?
}


/**
 * check if required POST parameters are set
 *
 * example:
 * action_required_parameters('id', 'name');
 */
function action_required_parameters() {
	foreach ( func_get_args() as $arg ) {
		if ( !isset($_POST[$arg]) ) {
			warning("Parameter missing.");
			redirect();
		}
	}
}


/**
 * used by proposals.php and proposal.php
 */
function action_proposal_select_period() {

	Login::access_action("admin");
	action_required_parameters('issue', 'period');

	$issue = new Issue($_POST['issue']);
	if (!$issue) {
		warning("The requested issue does not exist!");
		redirect();
	}

	$period = new Period($_POST['period']);
	if (!$period) {
		warning("The selected period does not exist!");
		redirect();
	}

	$available =& $issue->available_periods();
	if (!isset($available[$period->id])) {
		warning("The selected period is not available for the issue!");
		redirect();
	}

	$issue->period = $period->id;
	$issue->update(["period"]);

	redirect(URI::same(true)."#issue".$issue->id);
}


/**
 * POST form open tag with CSRF token
 *
 * @param string  $url                      (optional) leave empty to stay on same URI, set to BN to remove parameters
 * @param string  $attributes               (optional) HTML attributes
 * @param string  $class                    (optional) HTML class attribute
 * @param string  $id                       (optional) HTML id attribute
 * @param boolean $confirm_exit_if_modified (optional) confirm leaving the page if the form contains unsaved data
 */
function form($url="", $attributes="", $class="", $id="", $confirm_exit_if_modified=false) {

	if ($attributes) $attributes = " ".$attributes;
	if ($id) $attributes .= ' id="'.$id.'"';
	if ($confirm_exit_if_modified) $class = ltrim($class." check_unload");
	if ($class) $attributes .= ' class="'.$class.'"';

	if ($confirm_exit_if_modified) {
		static $loaded = false;
		if (!$loaded) {
?>
<script src="js/confirm_exit_if_modified.js"></script>
<?
			$GLOBALS['js_open_link'] = true;
			$loaded = true;
		}
?>
<script>
confirmExitIfModified("<?=$id?>", "<?=_("You have unsaved changes.")?>");
</script>
<?
		$attributes .= ' onsubmit="window.onbeforeunload=null"';
	}

?>
<form action="<?=$url?>" method="POST"<?=$attributes?>>
<input type="hidden" name="csrf" value="<?=$_SESSION['csrf']?>">
<?
}


/**
 * hidden input field
 *
 * @param string  $name
 * @param string  $value
 */
function input_hidden($name, $value) {
?>
<input type="hidden" name="<?=$name?>" value="<?=h($value)?>">
<?
}


/**
 * text input field
 *
 * @param string  $name
 * @param string  $value
 * @param boolean $disabled   (optional)
 * @param string  $attributes (optional)
 */
function input_text($name, $value, $disabled=false, $attributes="") {
?>
<input type="text" name="<?=$name?>" value="<?=h($value)?>"<?
	if ($disabled) { ?> disabled<? }
	if ($attributes) { ?> <?=$attributes; }
	?>>
<?
}


/**
 * textarea
 *
 * @param string  $name
 * @param string  $value
 * @param boolean $disabled   (optional)
 * @param string  $attributes (optional)
 */
function input_textarea($name, $value, $disabled=false, $attributes="") {
?>
<textarea name="<?=$name?>"<?
	if ($disabled) { ?> disabled<? }
	if ($attributes) { ?> <?=$attributes; }
	?>><?=h($value)?></textarea>
<?
}


/**
 * checkbox
 *
 * @param string  $name
 * @param string  $value
 * @param boolean $checked    (optional)
 * @param boolean $disabled   (optional)
 * @param string  $attributes (optional)
 */
function input_checkbox($name, $value, $checked=false, $disabled=false, $attributes="") {
?>
<input type="checkbox" name="<?=$name?>" value="<?=h($value)?>"<?
	if ($checked) { ?> checked<? }
	if ($disabled) { ?> disabled<? }
	if ($attributes) { ?> <?=$attributes; }
	?>>
<?
}


/**
 * drop down menu
 *
 * @param string  $name
 * @param array   $options
 * @param mixed   $selected   (optional)
 * @param string  $attributes (optional)
 */
function input_select($name, array $options, $selected=false, $attributes="") {
?>
<select name="<?=$name?>"<?
	if ($attributes) { ?> <?=$attributes; }
	?>>
<?
	foreach ( $options as $key => $value ) {
?>
 <option value="<?=$key?>"<?
		if ($key==$selected) { ?> selected class="selected"<? }
		?>><?=$value?></option>
<?
	}
?>
</select>
<?
}


/**
 * submit button
 *
 * @param string  $value
 */
function input_submit($value) {
?>
	<input type="submit" value="<?=h($value)?>">
<?
}


/**
 *
 */
function form_end() {
?>
</form>
<?
}


/**
 * display signs for true or false
 *
 * @param boolean $value
 */
function display_checked($value) {
	if ($value) echo "&#10003;"; else echo "-";
}


/**
 * return CSS classes with alternating background colors
 *
 * @param mixed   $change (optional) if this value changes, the color changes
 * @param mixed   $suffix (optional) for subclasses
 * @return string
 */
function stripes($change=false, $suffix="") {
	static $colorid = 1; // start with td0
	static $change_last = null;
	if ($change===false or $change_last != $change) {
		$colorid = ($colorid + 1) % 2;
	}
	$change_last = $change;
	return "td".$colorid.$suffix;
}


/**
 * display help text
 *
 * default for logged in members:      show all help
 * default for not logged in sessions: hide all help
 *
 * This function should not be used on pages with forms, because users will lose their already filled in data if they click on the help buttons.
 *
 * @param string  $anchor (optional) for additional help messages on the same page
 * @param boolean $h1     (optional)
 */
function help($anchor="", $h1=false) {
	if ($anchor) {
		$link_anchor = "#help_".$anchor;
		$id = ' id="help_'.$anchor.'"';
		$identifier = BN."#".$anchor;
	} else {
		$link_anchor = "";
		$id = '';
		$identifier = BN;
	}
	if (Login::$member) {
		$show = !in_array($identifier, Login::$member->hide_help());
	} else {
		if ( isset($_SESSION['show_help']) ) {
			$show = in_array($identifier, $_SESSION['show_help']);
		} else {
			$show = false;
		}
	}
	if ($show) {
?>
<section class="help"<?=$id?>>
<?
		form(URI::same().$link_anchor, 'class="hide_help"');
		if ($anchor) {
?>
<input type="hidden" name="anchor" value="<?=$anchor?>">
<?
		}
?>
<input type="hidden" name="action" value="hide_help">
<input type="submit" value="<?=_("hide help")?>">
<?
		form_end();
		// read help content
		$display = false;
		$list = false;
		foreach ( file("locale/help_".LANG.".html", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
			if ($display) {
				$line_begin = mb_substr($line, 0, 3);
				if ($line_begin == "===") break;
				// replace placeholders
				if ( strpos($line, "%")!==false ) {
					static $replace;
					if (!$replace) {
						$replace = array(
							'%REQUIRED_PROPONENTS'   => REQUIRED_PROPONENTS,
							'%QUORUM_VOTINGMODE'     => numden([QUORUM_VOTINGMODE_NUM, QUORUM_VOTINGMODE_DEN]),
							'%MAIL_SUPPORT'          => MAIL_SUPPORT,
							'%COMMENT_EDIT_INTERVAL' => translate_time_interval(COMMENT_EDIT_INTERVAL)
						);
					}
					$line = strtr($line, $replace);
				}
				// list / paragraph
				if ($line_begin == " * ") {
					if (!$list) {
						$list = true;
?>
<ul>
<?
					}
?>
	<li><?=mb_substr($line, 3)?></li>
<?
				} else {
					if ($list) {
?>
</ul>
<?
						$list = false;
					}
?>
<p><?=$line?></p>
<?
				}
			} elseif ($line == "===".$identifier) {
				$display = true;
			}
		}
?>
</section>
<?
	} else {
		form(URI::same().$link_anchor, 'class="show_help'.($h1?' h1':'').'"'.$id);
		if ($anchor) {
?>
<input type="hidden" name="anchor" value="<?=$anchor?>">
<?
		}
?>
<input type="hidden" name="action" value="show_help">
<input type="submit" value="<?=_("show help")?>">
<?
		form_end();
	}
}


/**
 * hide help on a page
 */
function hide_help() {
	if ( !empty($_POST['anchor']) and preg_match("/^[a-z]+$/", $_POST['anchor']) ) {
		$identifier = BN."#".$_POST['anchor'];
	} else {
		$identifier = BN;
	}
	if (Login::$member) {
		$hide = Login::$member->hide_help();
		if (!in_array($identifier, $hide)) {
			$hide[] = $identifier;
			Login::$member->update_help($hide);
		}
	} else {
		if (!empty($_SESSION['show_help'])) array_remove_value($_SESSION['show_help'], $identifier);
	}
}


/**
 * show help on a page
 */
function show_help() {
	if ( !empty($_POST['anchor']) and preg_match("/^[a-z]+$/", $_POST['anchor']) ) {
		$identifier = BN."#".$_POST['anchor'];
	} else {
		$identifier = BN;
	}
	if (Login::$member) {
		$hide = Login::$member->hide_help();
		array_remove_value($hide, $identifier);
		Login::$member->update_help($hide);
	} else {
		if (empty($_SESSION['show_help'])) {
			$_SESSION['show_help'] = array($identifier);
		} elseif (!in_array($identifier, $_SESSION['show_help'])) {
			$_SESSION['show_help'][] = $identifier;
		}
	}
}


/**
 * format content without changing it
 *
 * @param string  $text
 * @return string
 */
function content2html($text) {
	return preg_replace(
		array('#https?://\S+#i',     '#\S+@\S+\.[a-z]+#i',         "#''[^'\n]+''#"),
		array('<a href="$0">$0</a>', '<a href="mailto:$0">$0</a>', '<i>$0</i>'    ),
		nl2br(h($text), false)
	);
}


/**
 * output alt and title attributes for images at once
 *
 * @param string  $text
 */
function alt($text) {
	?>alt="<?=$text?>" title="<?=$text?>"<?
}
