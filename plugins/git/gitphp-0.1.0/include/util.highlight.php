<?php
/*
 *  util.highlight.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - highlight a string
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function highlight($haystack, $needle, $highlightclass, $trimlen = NULL, $escape = false)
{
	if (preg_match("/(.*)(" . quotemeta($needle) . ")(.*)/i",$haystack,$regs)) {
		if (isset($trimlen) && ($trimlen > 0)) {
			$linelen = strlen($regs[0]);
			if ($linelen > $trimlen) {
				$matchlen = strlen($regs[2]);
				$remain = floor(($trimlen - $matchlen) / 2);
				$leftlen = strlen($regs[1]);
				$rightlen = strlen($regs[3]);
				if ($leftlen > $remain) {
					$leftremain = $remain;
					if ($rightlen < $remain)
						$leftremain += ($remain - $rightlen);
					$regs[1] = "..." . substr($regs[1], ($leftlen - ($leftremain - 3)));
				}
				if ($rightlen > $remain) {
					$rightremain = $remain;
					if ($leftlen < $remain)
						$rightremain += ($remain - $leftlen);
					$regs[3] = substr($regs[3],0,$rightremain-3) . "...";
				}
			}
		}
		if ($escape) {
			$regs[1] = htmlspecialchars($regs[1]);
			$regs[2] = htmlspecialchars($regs[2]);
			$regs[3] = htmlspecialchars($regs[3]);
		}
		$ret = $regs[1] . "<span";
		if ($highlightclass)
			$ret .= " class=\"" . $highlightclass . "\"";
		$ret .= ">" . $regs[2] . "</span>" . $regs[3];
		return $ret;
	}

	return false;
}

?>
