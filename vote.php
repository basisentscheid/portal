<?
/**
 * vote
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


require "inc/common.php";

$issue = new Issue(@$_GET['issue']);
if (!$issue->id) {
	error(_("The requested issue does not exist."));
}
if ($issue->state == 'finished' or $issue->state == 'cleared') {
	error(_("The voting on this issue is already closed."));
} elseif ($issue->state != 'voting') {
	error(_("The issue is not in voting state."));
}

$ngroup = $issue->area()->ngroup();
Login::access("entitled", $ngroup->id);

$token = $issue->vote_token();
if (!$token) {
	error(_("You can not vote in this voting period, because you were not yet entitled when the voting started."));
}

if ($action) {
	switch ($action) {
	case "submit":
		$issue->vote($token, $_POST['vote']);
		//redirect("proposals.php?ngroup=".$ngroup->id."&filter=voting");
		redirect();
		break;
	default:
		warning(_("Unknown action"));
		redirect();
	}
}


html_head(_("Vote"));

form("vote.php?issue=".$issue->id);
?>
<input type="hidden" name="action" value="submit">
<table class="proposals">
<?

list($proposals, $submitted) = $issue->proposals_list(true);
Issue::display_proposals_th(false, count($proposals) > 1);

$sql = "SELECT vote FROM vote WHERE token=".DB::esc($token)." ORDER BY votetime DESC";
$result = DB::query($sql);
// fetch only the first record, which is the latest vote
if ( $row = DB::fetch_assoc($result) ) {
	$vote = unserialize($row['vote']);
} else {
	$vote = array();
	foreach ( $proposals as $proposal ) {
		$vote[$proposal->id]['acceptance'] = -1; // default is abstention
		if (count($proposals) > 1) $vote[$proposal->id]['score'] = 0; // default is 0
	}
}

$issue->display_proposals($proposals, $submitted, count($proposals), false, 0, $vote);
?>
	<tr>
		<td></td>
		<td<?
if (count($proposals) > 1) { ?> colspan="2"<? }
?> class="th"><input type="submit" value="<?=_("Submit vote")?>"></td>
	</tr>
</table>
<?
form_end();
?>

<div class="clearfix"></div>
<?

html_foot();