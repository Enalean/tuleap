<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('HTML_Element_Selectbox.class.php');

/**
 * Define an html selectbox field for rank (at the beiginning, at the end, after XX, after YY, after ZZZ)
 */
class HTML_Element_Selectbox_Rank extends HTML_Element_Selectbox
{

    /**
     * @param $label String the label of the field
     * @param $name String  the name of the input
     * @param $value mixed  the current value of the field
     * @param $id int The item id
     * @param $siblings array the sibling of the item array('id' => 123, 'name' => 'Item display name', 'rank' => 4)
     */
    public function __construct($label, $name, $value, $id, $siblings)
    {
        parent::__construct($label, $name, $value);

        $this->addOption(new HTML_Element_Option($GLOBALS['Language']->getText('global', 'at_the_beginning'), 'beginning', false));
        $this->addOption(new HTML_Element_Option($GLOBALS['Language']->getText('global', 'at_the_end'), 'end', false));
        $this->addOption(new HTML_Element_Option('--', '--', false));
        foreach ($siblings as $i => $item) {
            if ($item['id'] != $id) {
                $this->addOption(
                    new HTML_Element_Option(
                        $GLOBALS['Language']->getText('global', 'after', $item['name']),
                        $item['rank'] + 1,
                        (isset($siblings[$i + 1]) && $siblings[$i + 1]['id'] == $id)
                    )
                );
            }
        }
    }
}
