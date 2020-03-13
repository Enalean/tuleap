<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * highlight smarty modifier
 *
 * @param string $haystack string to search in
 * @param string $needle substring to search for
 * @param int $trimlen length to trim string to
 * @param bool $escape true to html escape the string
 * @param string $highlightclass CSS class to highlight with
 * @return string highlighted string
 */
function smarty_modifier_highlight($haystack, $needle, $trimlen = null, $escape = true, $highlightclass = 'git-repository-highlight')
{
    if (preg_match("/(.*)(" . quotemeta($needle) . ")(.*)/i", $haystack, $regs)) {
        if (isset($trimlen) && ($trimlen > 0)) {
            $linelen = strlen($regs[0]);
            if ($linelen > $trimlen) {
                $matchlen = strlen($regs[2]);
                $remain = floor(($trimlen - $matchlen) / 2);
                $leftlen = strlen($regs[1]);
                $rightlen = strlen($regs[3]);
                if ($leftlen > $remain) {
                    $leftremain = $remain;
                    if ($rightlen < $remain) {
                        $leftremain += ($remain - $rightlen);
                    }
                    $regs[1] = "…" . substr($regs[1], ($leftlen - ($leftremain - 3)));
                }
                if ($rightlen > $remain) {
                    $rightremain = $remain;
                    if ($leftlen < $remain) {
                        $rightremain += ($remain - $leftlen);
                    }
                    $regs[3] = substr($regs[3], 0, $rightremain - 3) . "…";
                }
            }
        }
        if ($escape) {
            $regs[1] = htmlspecialchars($regs[1]);
            $regs[2] = htmlspecialchars($regs[2]);
            $regs[3] = htmlspecialchars($regs[3]);
        }
        $ret = $regs[1] . "<span";
        if ($highlightclass) {
            $ret .= " class=\"" . $highlightclass . "\"";
        }
        $ret .= ">" . $regs[2] . "</span>" . $regs[3];
        return $ret;
    }

    return $haystack;
}
