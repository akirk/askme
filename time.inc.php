<?php

class Time {
	private static $now = null;
	public static function since($time, $short = false, $return_date_after = 40) {
		if (!self::$now) self::$now = new DateTime(null);
		return self::_duration(self::$now, $time, $short, $return_date_after);
	}

	public static function duration($since, $until, $short = false) {
		return self::_duration($since, $until, $short);
	}

	private static function _duration($since, $until, $short = false, $return_date_after = 40) {
		if (!$since instanceof DateTime) $since = new DateTime(substr($since, 4, 1) == "-" ? $since : date("Y-m-d H:i:s", $since));
		if (!$until instanceof DateTime) $until = new DateTime(substr($until, 4, 1) == "-" ? $until : date("Y-m-d H:i:s", $until));
		$duration = $since->diff($until);
		if ($return_date_after > 0 && $duration->format("%a") >= $return_date_after) {
			if ($short) return str_replace("%date", $until->format(_("M j, Y")), _("on %date"));;
			return str_replace("%date", $until->format(_("l, F j, Y")), _("on %date"));;
		}
		return Time::relativeTime($duration);
	}

	private static function relativeTime($duration) {
		$t = array();

		$subtract_days = 0;
		$s = $duration->format("%y");
		if ($s) {
			$x = $s . " " . ($s > 1 ? msg_years : msg_year);
			$subtract_days = $s * 365.25;
			$t[] = $x;
		}

		$s = $duration->format("%m");
		if ($s) {
			$x = $s . " " . ($s > 1 ? msg_months : msg_month);
			$subtract_days += $s * 30;
			$t[] = $x;
		}

		$s = $duration->format("%a");
		$s = ceil($s - $subtract_days);
		if ($s) {
			$x = $s . " " . ($s > 1 ? msg_days : msg_day);
			$t[] = $x;
		}

		$s = $duration->format("%h");
		if (empty($t) && $s) {
			$x = $s . " " . ($s > 1 ? msg_hours : msg_hour);
			$t[] = $x;
		}

		$s = $duration->format("%i");
		if (empty($t) && $s) {
			$x = $s . " " . ($s > 1 ? msg_minutes : msg_minute);
			$t[] = $x;
		}

		if (empty($t)) {
			$s = $duration->format("%s");
			if ($s) {
				$x = $s . " " . ($s > 1 ? msg_seconds : msg_second);
				$t[] = $x;
			}
		}

		if (empty($t)) {
			return msg_just_now;
		}

		$ret = "";
		$s = "";
		for ($i = min(1, count($t) - 1); $i >= 0; $i--) {
			$ret = $t[$i] . $s . $ret;
			$s = ", ";
		}
		if ($duration->format("%R") != "-") return $ret;

		return str_replace("%time", $ret, msg_time_ago);
	}
}
