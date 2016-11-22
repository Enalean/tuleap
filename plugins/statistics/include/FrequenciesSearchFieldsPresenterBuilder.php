<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Statistics;

use EventManager;
use Statistics_Event;

class FrequenciesSearchFieldsPresenterBuilder
{
    public function build(
        array $type_values,
        $date_value
    ) {
        $type_options = $this->getListOfTypeValuePresenter($type_values);

        return new FrequenciesSearchFieldsPresenter($type_options, $date_value);
    }

    private function getListOfTypeValuePresenter(array $type_values)
    {
        $all_data = array(
            'session'   => $GLOBALS['Language']->getText('plugin_statistics', 'session_type'),
            'user'      => $GLOBALS['Language']->getText('plugin_statistics', 'user_type'),
            'forum'     => $GLOBALS['Language']->getText('plugin_statistics', 'forum_type'),
            'filedl'    => $GLOBALS['Language']->getText('plugin_statistics', 'filedl_type'),
            'file'      => $GLOBALS['Language']->getText('plugin_statistics', 'file_type'),
            'groups'    => $GLOBALS['Language']->getText('plugin_statistics', 'groups_type'),
            'docdl'     => $GLOBALS['Language']->getText('plugin_statistics', 'docdl_type'),
            'wikidl'    => $GLOBALS['Language']->getText('plugin_statistics', 'wikidl_type'),
            'oartifact' => $GLOBALS['Language']->getText('plugin_statistics', 'oartifact_type'),
            'cartifact' => $GLOBALS['Language']->getText('plugin_statistics', 'cartifact_type'),
        );

        EventManager::instance()->processEvent(
            Statistics_Event::FREQUENCE_STAT_ENTRIES,
            array('entries' => &$all_data)
        );

        $type_options = array();

        foreach ($all_data as $key => $value) {
            $type_options[] = $this->getTypeValuePresenter($key, $type_values, $value);
        }

        return $type_options;
    }

    private function getTypeValuePresenter($type, $type_values, $label)
    {
        $selected = in_array($type, $type_values);

        return array(
            'value'       => $type,
            'is_selected' => $selected,
            'label'       => $label
        );
    }
}
