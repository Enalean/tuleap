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

namespace Tuleap\Layout;

class PaginationPresenter
{
    public $limit;
    public $offset;
    public $nb_displayed;
    public $nb_total;
    public $offset_end_range;

    public $first_url;
    public $previous_url;
    public $next_url;
    public $last_url;

    public $first_label;
    public $previous_label;
    public $next_label;
    public $last_label;
    public $of;

    public $first_is_disabled;
    public $previous_is_disabled;
    public $next_is_disabled;
    public $last_is_disabled;
    public $has_result;
    public $offset_start_range;

    public function __construct($limit, $offset, $nb_displayed, $nb_total, $base_url, $default_params)
    {
        $this->limit        = $limit;
        $this->offset       = $offset;
        $this->nb_displayed = $nb_displayed;
        $this->nb_total     = $nb_total;
        $this->has_result   = $nb_total > 0;

        $this->offset_start_range = $offset + 1;
        $this->offset_end_range   = $offset + $nb_displayed;

        $this->first_is_disabled    = false;
        $this->previous_is_disabled = false;
        $this->next_is_disabled     = false;
        $this->last_is_disabled     = false;

        $this->first_url    = $base_url . '?' . http_build_query(array('offset' => 0) + $default_params);
        $this->previous_url = $base_url . '?' . http_build_query(array('offset' => $offset - $limit) + $default_params);
        $this->next_url     = $base_url . '?' . http_build_query(array('offset' => $offset + $limit) + $default_params);
        $this->last_url     = $base_url . '?' . http_build_query(array('offset' => $limit * floor($nb_total / $limit) - 1) + $default_params);

        $this->first_label    = $GLOBALS['Language']->getText('global', 'begin');
        $this->previous_label = $GLOBALS['Language']->getText('global', 'prev');
        $this->next_label     = $GLOBALS['Language']->getText('global', 'next');
        $this->last_label     = $GLOBALS['Language']->getText('global', 'end');
        $this->of             = $GLOBALS['Language']->getText('global', 'of');

        if ($offset <= 0) {
            $this->first_is_disabled = true;
            $this->first_url         = 'javascript:;';

            $this->previous_is_disabled = true;
            $this->previous_url         = 'javascript:;';
        }

        if (($offset + $limit) >= $nb_total) {
            $this->next_is_disabled = true;
            $this->next_url         = 'javascript:;';

            $this->last_is_disabled = true;
            $this->last_url         = 'javascript:;';
        }
    }
}
