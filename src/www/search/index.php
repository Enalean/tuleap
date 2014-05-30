<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once('pre.php');

$router = new Search_SearchRouter();
$router->route($request);

/* KEPT DURING REFACTORING

   // This code puts the nice next/prev.
if ( !$pagination_handled && !$no_rows && ( ($rows_returned > $rows) || ($offset != 0) ) ) {

	echo "<BR>\n";

	echo "<TABLE class=\"boxitem\" WIDTH=\"100%\" CELLPADDING=\"5\" CELLSPACING=\"0\">\n";
	echo "<TR>\n";
	echo "\t<TD ALIGN=\"left\">";
	if ($offset != 0) {
		echo "<span class=\"normal\"><B>";
		echo "<A HREF=\"javascript:history.back()\"><B><IMG SRC=\"".util_get_image_theme('t2.png')."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE> ".$Language->getText('search_index','prev_res')." </A></B></span>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD>\n\t<TD ALIGN=\"right\">";
	if ( $rows_returned > $rows) {
		echo "<span class=\"normal\"><B>";
		echo "<A HREF=\"/search/?type_of_search=$type_of_search&words=".urlencode($words)."&offset=".($offset+$rows);
		if ( $type_of_search == 'bugs' ) {
			echo "&group_id=$group_id&is_bug_page=1";
		}
		if ( $type_of_search == 'forums' ) {
			echo "&forum_id=$forum_id&is_forum_page=1";
		}
                if ( $exact ) {
			echo "&exact=1";
		}
		if ( $type_of_search == 'tracker' ) {
			echo "&group_id=$group_id&atid=$atid";
		}
		echo "\"><B>".$Language->getText('search_index','next_res')." <IMG SRC=\"".util_get_image_theme('t.png')."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE></A></B></span>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD>\n</TR>\n";
	echo "</TABLE>\n";
}
 *
 */
