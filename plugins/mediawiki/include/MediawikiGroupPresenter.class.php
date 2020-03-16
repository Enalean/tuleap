<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class MediawikiGroupPresenter
{

    /** @var ProjectUGroup[] */
    private $available_ugroups;

    /** @var string */
    private $mediawiki_group_label;

    /** @var string */
    private $mediawiki_group_id;

    /** @var ProjectUGroup[] */
    private $current_mapping;

    public function __construct($mediawiki_group_id, $mediawiki_group_label, $available_ugroups, $mapping)
    {
        $this->mediawiki_group_id    = $mediawiki_group_id;
        $this->available_ugroups     = $available_ugroups;
        $this->mediawiki_group_label = $mediawiki_group_label;
        $this->current_mapping       = $mapping;
    }

    public function tuleap_group_label()
    {
        return 'Tuleap groups';
    }

    public function mediawiki_group_label()
    {
        return $this->mediawiki_group_label . ' (Mediawiki)';
    }

    public function available_groups()
    {
        $selector = array(
            'name'     => 'available_' . $this->mediawiki_group_id . '[]',
            'class'    => 'forge_mw_available_groups',
            'options'  => array()
        );
        foreach ($this->available_ugroups as $ugroup) {
            $selector['options'][] = array(
                'value'    => $ugroup->getId(),
                'label'    => $ugroup->getTranslatedName(),
                'selected' => false,
            );
        }
        return $selector;
    }

    public function selected_groups()
    {
        $selector = array(
            'name'     => 'selected_' . $this->mediawiki_group_id . '[]',
            'class'    => 'forge_mw_selected_groups',
            'options'  => array()
        );

        foreach ($this->current_mapping as $ugroup) {
            $selector['options'][] = array(
                'value'    => $ugroup->getId(),
                'label'    => $ugroup->getTranslatedName(),
                'selected' => false,
            );
        }

        return $selector;
    }

    public function hidden_selected_groups_name()
    {
        return 'hidden_selected_' . $this->mediawiki_group_id;
    }

    public function hidden_selected_groups_value()
    {
        $ids = array();
        foreach ($this->current_mapping as $ugroup) {
            $ids[] = $ugroup->getId();
        }
        return implode(',', $ids);
    }
}
