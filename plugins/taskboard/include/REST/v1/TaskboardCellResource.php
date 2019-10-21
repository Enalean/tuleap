<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Tuleap\AgileDashboard\REST\v1\OrderRepresentation;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\UserManager;

class TaskboardCellResource extends AuthenticatedResource
{
    /**
     * @url OPTIONS {swimlane_id}/column/{column_id}
     *
     * @param int $swimlane_id
     * @param int $column_id
     */
    public function options(int $swimlane_id, int $column_id)
    {
        Header::allowOptionsPatch();
    }

    /**
     * Patch Taskboard cell
     *
     * Reorder cards in a taskboard cell.
     *
     * <pre>
     * /!\ This REST route is under construction and subject to changes /!\
     * </pre>
     *
     * <br>
     * Example:
     * <pre>
     * "order": {
     *   "ids": [123, 789, 1001],
     *   "direction": "before",
     *   "compared_to": 456
     * }
     * </pre>
     *
     * <br>
     * The resulting order will be: <pre>[…, 123, 789, 1001, 456, …]</pre>
     *
     * @url    PATCH {swimlane_id}/column/{column_id}
     * @access protected
     *
     * @param int                                                $swimlane_id Artifact Id of the swimlane containing the cell
     * @param int                                                $column_id   Id of the cell's column
     * @param \Tuleap\AgileDashboard\REST\v1\OrderRepresentation $order       Order of the cards in the cell
     * @throws RestException 404
     */
    public function patch(int $swimlane_id, int $column_id, ?OrderRepresentation $order = null): void
    {
        $this->checkAccess();
        $patcher = CellPatcher::build();
        $patcher->patchCell($swimlane_id, $order);
    }
}
