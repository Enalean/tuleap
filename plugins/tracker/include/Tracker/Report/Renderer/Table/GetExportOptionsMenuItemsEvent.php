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

class GetExportOptionsMenuItemsEvent implements Dispatchable
{
    const NAME = 'getExportOptionsMenuItems';
    /**
     * @var \Tracker_Report_Renderer_Table
     */
    private $renderer_table;
    /**
     * @var \Tracker_Report
     */
    private $report;
    /**
     * @var string
     */
    private $export_items_as_html;
    /**
     * @var string
     */
    private $additional_content;

    public function __construct(\Tracker_Report_Renderer_Table $renderer_table)
    {
        $this->renderer_table       = $renderer_table;
        $this->report               = $renderer_table->report;
        $this->export_items_as_html = '';
        $this->additional_content   = '';
    }

    public function addExportItem($item_as_html)
    {
        $this->export_items_as_html .= $item_as_html;
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
     * @return string
     */
    public function getItems()
    {
        return $this->export_items_as_html;
    }

    public function addAdditionalContentThatGoesOutsideOfTheMenu($additional_content_as_html)
    {
        $this->additional_content .= $additional_content_as_html;
    }

    public function getAdditionalContentThatGoesOutsideOfTheMenu()
    {
        return $this->additional_content;
    }
}
