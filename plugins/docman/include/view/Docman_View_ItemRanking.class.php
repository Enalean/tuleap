<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright © STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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

class Docman_View_ItemRanking
{
    public $dropDownName;
    public $selectedValue;

    public function __construct()
    {
        $this->selectedValue = 'beginning';
    }

    public function setDropDownName($v)
    {
        $this->dropDownName = $v;
    }


    public function setSelectedValue($v)
    {
        if (is_numeric($v)) {
            $this->selectedValue = (int) $v;
        } else {
            $this->selectedValue = $v;
        }
    }

    public function getDropDownWidget($parentItem)
    {
        $itemFactory = Docman_ItemFactory::instance($parentItem->getGroupId());
        $brotherIter = $itemFactory->getChildrenFromParent($parentItem);

        $vals = array('beginning', 'end', '--');
        $texts = array(dgettext('tuleap-docman', 'At the beginning'),
                       dgettext('tuleap-docman', 'At the end'),
                       '----');
        $i = 3;

        $pm = Docman_PermissionsManager::instance($parentItem->getGroupId());
        $um = UserManager::instance();
        $user = $um->getCurrentUser();

        $hp = Codendi_HTMLPurifier::instance();
        $brotherIter->rewind();
        while ($brotherIter->valid()) {
            $item = $brotherIter->current();
            if ($pm->userCanWrite($user, $item->getId())) {
                $vals[$i]  = $item->getRank() + 1;
                $texts[$i] = dgettext('tuleap-docman', 'After') . ' ' . $hp->purify($item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML);
                $i++;
            }
            $brotherIter->next();
        }

        // Cannot use html_build_select_box_from_arrays because of to lasy == operator
        // In this case because of cast string values are converted to 0 on cmp. So if
        // there is a rank == 0 ... so bad :/
        $html = '';
        $html = dgettext('tuleap-docman', 'Position:') . ' ';

        $html .= '<select name="' . $this->dropDownName . '">' . "\n";
        $maxOpts = count($vals);
        for ($i = 0; $i < $maxOpts; $i++) {
            $selected = '';
            if ($vals[$i] === $this->selectedValue) {
                $selected = ' selected="selected"';
            }
            $html .= '<option value="' . $vals[$i] . '"' . $selected . '>' . $texts[$i] . '</option>' . "\n";
        }
        $html .= '</select>';

        return $html;
    }
}
