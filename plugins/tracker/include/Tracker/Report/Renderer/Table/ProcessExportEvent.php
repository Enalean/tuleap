<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Renderer\Table;

use Tuleap\Event\Dispatchable;

class ProcessExportEvent implements Dispatchable
{
    const NAME = 'processExport';
    /**
     * @var array
     */
    private $renderer_parameters;
    /**
     * @var \Tracker_Report_Renderer_Table
     */
    private $renderer_table;
    /**
     * @var \Tracker_Report
     */
    private $report;
    /**
     * @var \PFUser
     */
    private $current_user;
    /**
     * @var string
     */
    private $server_url;

    public function __construct(
        array $renderer_parameters,
        \Tracker_Report_Renderer_Table $renderer_table,
        \PFUser $current_user,
        $server_url
    ) {
        $this->renderer_parameters = $renderer_parameters;
        $this->renderer_table      = $renderer_table;
        $this->report              = $renderer_table->report;
        $this->current_user        = $current_user;
        $this->server_url          = $server_url;
    }

    public function hasKeyInParameters($key)
    {
        return isset($this->renderer_parameters[$key]) && $this->renderer_parameters[$key];
    }

    /**
     * @return \Tracker_Report_Renderer_Table
     */
    public function getRendererTable()
    {
        return $this->renderer_table;
    }

    /**
     * @return \Tracker_Report
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @return \PFUser
     */
    public function getCurrentUser()
    {
        return $this->current_user;
    }

    /**
     * @return string
     */
    public function getServerUrl()
    {
        return $this->server_url;
    }
}
