<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Layout;

use Codendi_Request;
use Event;
use EventManager;
use ForgeConfig;

class SearchFormPresenterBuilder
{
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var Codendi_Request
     */
    private $request;

    public function __construct(EventManager $event_manager, Codendi_Request $request)
    {
        $this->event_manager = $event_manager;
        $this->request = $request;
    }

    public function build(): SearchFormPresenter
    {
        $type_of_search = $this->request->get('type_of_search');
        $group_id       = $this->request->get('group_id');

        $search_entries = [];
        $hidden = [];

        if ($group_id) {
            $hidden[] = [
                'name'  => 'group_id',
                'value' => $group_id
            ];

            if ($this->request->exist('forum_id')) {
                $search_entries[] = [
                    'value'    => 'forums',
                    'selected' => true,
                ];
                $hidden[] = [
                    'name'  => 'forum_id',
                    'value' => $this->request->get('forum_id')
                ];
            }
            if ($this->request->exist('atid')) {
                $search_entries[] = [
                    'value'    => 'tracker',
                    'selected' => true,
                ];
                $hidden[] = [
                    'name'  => 'atid',
                    'value' => $this->request->get('atid')
                ];
            }
            if (strpos($_SERVER['REQUEST_URI'], '/wiki/') === 0) {
                $search_entries[] = [
                    'value'    => 'wiki',
                    'selected' => true,
                ];
            }
        }

        if (ForgeConfig::get('sys_use_trove')) {
            $search_entries[] = [
                'value' => 'soft',
                'selected' => false,
            ];
        }

        $search_entries[] = [
            'value' => 'people',
            'selected' => false,
        ];

        $this->event_manager->processEvent(
            Event::LAYOUT_SEARCH_ENTRY,
            [
                'type_of_search' => $type_of_search,
                'search_entries' => &$search_entries,
                'hidden_fields'  => &$hidden,
            ]
        );

        $selected_entry = $this->getSelectedOption($search_entries);

        return new SearchFormPresenter($selected_entry['value'], $hidden);
    }

    /**
     * @param array{array{value: string, selected: bool}} $search_entries
     *
     * @return array{value: string, selected: bool}
     */
    private function getSelectedOption(array $search_entries): array
    {
        $selected_option = $search_entries[0];

        foreach ($search_entries as $key => $search_entry) {
            if ($search_entry['selected']) {
                return $search_entries[$key];
            }
        }

        return $selected_option;
    }
}
