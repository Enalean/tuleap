<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

include __DIR__ . "/../vendor/autoload.php";
include __DIR__ . "/../include/manual_autoload.php";

$input = <<<EOS

field1_name = "f1"
or (field1_description = "desc1"
or field1_float != 2.5
or field2_float > 5.4)
or field2_int <= 2
and (field3_int between (1, 10)
AND field4_list in ("open", "closed", "blocked")
AND field5_list not in ("blocked", "archive",))
AND @comment = ""
AND (WITHOUT PARENT OR WITH PARENT ARTIFACT = 123 OR WITH PARENT TRACKER = 'epic')
AND (WITHOUT CHILDREN OR WITH CHILD ARTIFACT = 123 OR WITH CHILDREN TRACKER = 'epic')
AND (IS LINKED FROM OR IS LINKED TO TRACKER = 'epic' OR IS LINKED TO TRACKER != 'epic' )
AND (IS LINKED TO WITH TYPE 'fixed' OR IS NOT LINKED TO TRACKER = 'epic' WITH TYPE '_is_child')
AND (IS COVERED or IS COVERING artifact = 123) or is not covered by artifact = 244793
EOS;

try {
    $parser = new Parser();
    $result = $parser->parse($input);
    print_r($result);
} catch (SyntaxError $ex) {
    echo "Syntax error: " . $ex->getMessage()
        . ' At line ' . $ex->grammarLine
        . ' column ' . $ex->grammarColumn
        . ' offset ' . $ex->grammarOffset;
    exit(1);
}
