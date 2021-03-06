<?
/**
 * index.php
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


require "inc/common_http.php";

html_head(HOME_H1, true);

?>
<section>
<h2><?=_("Recently interesting issues")?></h2>
<table class="proposals">
<?
Issue::display_proposals_th(false, false, true);
if (Ngroup::$active_ngroups) {
	$sql = "SELECT issue.* FROM issue
		JOIN proposal ON proposal.issue = issue.id
		JOIN area ON area.id = issue.area
		WHERE area.ngroup IN (".join(",", Ngroup::$active_ngroups).")
		GROUP BY issue.id";
	if (Login::$ngroups) {
		// show issues in the groups of the logged in member first
		$sql .= ", area.ngroup
		ORDER BY area.ngroup IN (".join(", ", Login::$ngroups).") DESC,";
	} else {
		$sql .= "
		ORDER BY";
	}
	// order by highest activity, latest admitted, latest submitted
	$sql .= " SUM(proposal.activity) DESC, MAX(proposal.admitted) DESC NULLS LAST, MAX(proposal.submitted) DESC NULLS LAST, RANDOM()
		LIMIT 3";
	$result = DB::query($sql);
	while ( $issue = DB::fetch_object($result, "Issue") ) {
		/** @var $issue Issue */
		list($proposals, $submitted) = $issue->proposals_list();
?>
	<tr class="issue_separator"><td colspan="6"></td></tr>
<?
		$issue->display_proposals($proposals, $submitted, count($proposals), false, 0, [], true);
	}
}
?>
</table>
</section>

<section class="dates">
	<h2><?=_("Upcoming dates")?></h2>
	<table>
<?
if (Ngroup::$active_ngroups) {
	$dates = array();
	$info = array();
	foreach ( ["debate", "voting", "counting"] as $field ) {
		$result = DB::query("SELECT * FROM period WHERE $field > now() AND ngroup IN (".join(",", Ngroup::$active_ngroups).")");
		while ( $period = DB::fetch_object($result, "Period") ) {
			$dates[] = $period->$field;
			$info[] = array($field, $period);
		}
	}
	asort($dates);
	$line = 1;
	foreach ( $dates as $index => $time ) {
		list($field, $period) = $info[$index];
		if ( in_array($period->ngroup, Login::$ngroups) ) {
?>
			<tr class="<?=stripes()?> own" title="<?=_("You are member of this group.")?>">
<?
		} else {
?>
			<tr class="<?=stripes()?>">
<?
		}
?>
				<td><?=datetimeformat_smart($time)?></td>
				<td><?=$period->ngroup()->name?></td>
				<td><?
		$link = '<a href="periods.php?ngroup='.$period->ngroup.'&amp;hl='.$period->id.'">'.$period->id.'</a>';
		switch ($field) {
		case "debate":
			printf(_("debate in period %s starts"), $link);
			break;
		case "voting":
			printf(_("voting in period %s starts"), $link);
			break;
		case "counting":
			printf(_("voting in period %s closes"), $link);
			break;
		}
		?></td>
			</tr>
<?
		if (++$line > 10) break;
	}
}
?>
	</table>
</section>

<section class="ngroups">
<h2 id="ngroups"><?=_("Groups")?></h2>
<table>
<?
$sql = "SELECT * FROM ngroup ORDER BY name";
$result = DB::query($sql);
$ngroups = array();
while ( $ngroup = DB::fetch_object($result, "Ngroup") ) {
	$ngroups[] = $ngroup;
}
list($ngroups) = Ngroup::parent_sort_active_tree($ngroups);
foreach ($ngroups as $ngroup) {
	/** @var Ngroup $ngroup */
	if ( in_array($ngroup->id, Login::$ngroups) ) {
?>
	<tr class="<?=stripes()?> own" title="<?=_("You are member of this group.")?>">
<?
		$own = true;
	} else {
?>
	<tr class="<?=stripes()?>">
<?
		$own = false;
	}
	if ($ngroup->active) {
?>
		<td class="name"><?
		$ngroup->display_list_name($own);
		?></td>
		<td class="proposals"><a href="proposals.php?ngroup=<?=$ngroup->id?>"><?=_("Proposals")?> (<?=$ngroup->proposals_count()?>)</a><?
		if ( $own and Login::$member->eligible and Login::$member->verified and $nyvic = $ngroup->not_yet_voted_issues_count() ) {
			?> <a href="proposals.php?ngroup=<?=$ngroup->id?>&filter=voting"><?=Ngroup::not_yet_voted($nyvic)?></a><?
		}
		?></td>
		<td><a href="periods.php?ngroup=<?=$ngroup->id?>"><?=_("Periods")?> (<?=$ngroup->periods_count()?>)</a></td>
		<td><a href="areas.php?ngroup=<?=$ngroup->id?>"><?=_("Areas")?></a></td>
<?
	} else {
?>
		<td class="name inactive" title="<?=_("This group does not participate.")?>"><?
		$ngroup->display_list_name($own);
		?></td>
		<td></td>
		<td></td>
		<td></td>
<?
	}
?>
	</tr>
<?
}
?>
</table>
</section>

<div class="clearfix"></div>
<?

html_foot();
