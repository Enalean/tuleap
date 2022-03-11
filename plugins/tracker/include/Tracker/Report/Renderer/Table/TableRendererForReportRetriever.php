<?php
/**
 * Copyright (c) Enalean, 2022 — Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Renderer\Table;

use Tracker_Report;
use Tracker_Report_Renderer_Table;

class TableRendererForReportRetriever
{
    /**
     * @return Tracker_Report_Renderer_Table[]
     */
    public function getTableReportRendererForReport(Tracker_Report $report): array
    {
        $table_renderers = [];
        foreach ($report->getRenderers() as $renderer) {
            if ($renderer->getType() === Tracker_Report_Renderer_Table::TABLE) {
                $table_renderers[] = $renderer;
            }
        }

        return $table_renderers;
    }
}
