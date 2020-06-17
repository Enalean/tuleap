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

class DiskUsageGlobalPresenter
{
    public $header;
    /**
     * @var array
     */
    public $data_global;
    public $date;

    public function __construct(
        AdminHeaderPresenter $header,
        array $data_global,
        $date
    ) {
        $this->header = $header;

        $this->data_global = $data_global;
        $this->date        = date($GLOBALS['Language']->getText('system', 'datefmt_short'), strtotime($date));

        $this->pane_title = sprintf(dgettext('tuleap-statistics', 'Usage global on %1$s'), $this->date);
        $this->no_data    = dgettext('tuleap-statistics', 'There is no matching global data');
    }
}
