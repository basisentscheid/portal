<?
/**
 * Timebar
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


abstract class Timebar {

	const one_day = 86400; // 24 * 60 * 60
	const round_precision = 2;
	const day_width  = 1.9;  // em, must be equal to css: div.timebar div.day { width }
	const tick_width = 0.15; // em, must be equal to css: div.timebar div.datebar, div.timebar div.bar { width }


	/**
	 * display a time bar
	 *
	 * @param array   $times array( array(string <datetime>, string <label>, string <title>), ...
	 */
	public static function display(array $times) {
?>
<section class="timebar">
<?

		// convert to timestamps
		foreach ( $times as &$time ) {
			$time[0] = strtotime($time[0]);
			$time['class'] = "";
		}
		unset($time);

		usort($times, ["self", "sort_cmp"]);

		$from_time = strtotime(date("Y-m-d", $times[0][0]));
		$to_time = max(
			// show at least 2 days more than the last timestamp
			strtotime(date("Y-m-d", $times[count($times)-1][0])." + 2 days"),
			// show at least 10 days
			$from_time + 10 * self::one_day
		);

		// add "now" mark if it is within the time range
		$show_year = true;
		if (time() <= $to_time) {
			$times[] = array(time(), _("now"), _("now is %s"), 'class'=>" now");
			$show_year = false;
		}

		usort($times, ["self", "sort_cmp"]);

		// display multiple parts if there are big gaps
		$from_time_part = $from_time;
		$offset = 0;
		$days = ($to_time - $from_time) / self::one_day;
		if ($days > 30) {
			foreach ( $times as $index => $time ) {
				if (isset($times[$index+1]) and $times[$index+1][0] - $time[0] > self::one_day * 14) {
					$show_year = true;
					self::display_part(
						array_slice($times, $offset, $index+1-$offset),
						$from_time_part,
						strtotime(date("Y-m-d", $time[0])." + 5 days"),
						$show_year
					);
					$from_time_part = strtotime(date("Y-m-d", $times[$index+1][0])." - 3 days");
					$offset = $index+1;
				}
			}
		}
		self::display_part(
			array_slice($times, $offset),
			$from_time_part,
			$to_time,
			$show_year
		);

?>
</section>
<div class="clearfix"></div>
<?
	}


	/**
	 * display one continuous part
	 *
	 * @param array   $times
	 * @param integer $from_time
	 * @param integer $to_time
	 * @param boolean $show_year
	 */
	private function display_part(array $times, $from_time, $to_time, $show_year) {
		$second_width = self::day_width / self::one_day;
		$days = ($to_time - $from_time) / self::one_day;
?>
<div class="part">
	<div class="months"><?
		$month = 0;
		for ($t=$from_time; $t<=$to_time; $t+=self::one_day) {
			if ($t <= $to_time - self::one_day) {
				?><div class="day nowrap"><?
				if (date("n", $t)!=$month) {
					// show month name only if there is enough space
					if ( date("t", $t) - date("j", $t) > 3 ) {
						if ($show_year) {
							echo strftime("%B %Y", $t);
							$show_year = false; // show year only at the first month
						} else {
							echo strftime("%B", $t);
						}
					}
					$month = date("n", $t);
				}
				?></div><?
			}
		}
		?></div>
	<div class="days"><?
		for ($t=$from_time; $t<=$to_time; $t+=self::one_day) {
			if ($t <= $to_time - self::one_day) {
				?><div class="day month<?= date("m", $t) % 2 ?>"><?
				if (date("N", $t)>=6) {
					?><span class="weekend"><?=date("j", $t)?></span><?
				} else {
					echo date("j", $t);
				}
				?></div><?
			} else {
				?><div class="day">&nbsp;</div><?
			}
		}
		?></div>
	<div class="beam" style="width:calc(<?=round(self::day_width * $days, self::round_precision)."em"?> + 1px)"></div>
	<ul><li><div class="datespace" style="width:<?=round(($times[0][0] - $from_time) * $second_width - self::tick_width, self::round_precision)?>em"></div></li><?
		$line = 0;
		foreach ( $times as $index => $time ) {
			if (isset($times[$index+1])) {
				$width = max(0, round(($times[$index+1][0] - $time[0]) * $second_width - self::tick_width, self::round_precision));
			} else {
				// last one
				$width = 0;
			}
			?><li><div
		class="datebar<?=$time['class']?>" style="height:<?=$line+1?>em"><div class="mark"></div></div><div class="datespace<?=$time['class']?>" style="width:<?=$width?>em<?
			if ($line) { ?>;margin-top:<?=$line?>em<? }
			?>"
		title="<?=sprintf($time[2], date(DATETIMEYEAR_FORMAT, $time[0]))?>"><?=$time[1]?></div></li><?
			$line++;
			if ($width > 7) $line = 0;
		}
		?></ul></div>
<?
	}


	/**
	 * timestamp comparing function
	 *
	 * @param integer $a
	 * @param integer $b
	 * @return boolean
	 */
	private static function sort_cmp($a, $b) {
		if ($a[0] > $b[0]) return 1;
		if ($a[0] < $b[0]) return -1;
		return 0;
	}


}
