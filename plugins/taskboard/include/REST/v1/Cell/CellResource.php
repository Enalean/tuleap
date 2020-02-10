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

namespace Tuleap\Taskboard\REST\v1\Cell;

use Luracast\Restler\RestException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;

class CellResource extends AuthenticatedResource
{
    /**
     * @url OPTIONS {swimlane_id}/column/{column_id}
     *
     */
    public function options(int $swimlane_id, int $column_id)
    {
        Header::allowOptionsPatch();
    }

    /**
     * Patch Taskboard cell
     *
     * Move cards to a taskboard cell and/or reorder cards in a cell.
     *
     * <br>
     * Move a child card to a cell (changing its status):
     * <pre>
     * swimlane_id = 30, column_id = 7, payload = {"add": 123}
     * </pre>
     * Given that the card of id 123 is a child of the swimlane of id 30,
     * this will move it to the column of id 7, changing its status to the
     * corresponding mapping. For example if the column of id 7 is "On Going",
     * this will set the card's status field to "On Going".
     *
     * <br><br>
     * Move a solo card to a cell (changing its status):
     * <pre>
     * swimlane_id = 30, column_id = 7, payload = {"add": 30}
     * </pre>
     * When the swimlane of id 30 has no children (solo card), this will move it to
     * the column of id 7. "add" and "swimlane_id" must be the same for solo cards.
     *
     * <br><br>
     * Reorder a card in the column:
     * <pre>
     * payload = {"order": {
     *   "ids": [123, 789, 1001],
     *   "direction": "before",
     *   "compared_to": 456
     * }}
     * </pre>
     * The resulting order will be: <pre>[…, 123, 789, 1001, 456, …]</pre>
     *
     * You can change a card status and reorder it at the same time.
     *
     * @url    PATCH {swimlane_id}/column/{column_id}
     * @access protected
     *
     * @param int                     $swimlane_id Artifact Id of the swimlane containing the cell
     * @param int                     $column_id   Id of the cell's column
     * @param CellPatchRepresentation $payload     {@from body}
     * @throws RestException 404
     * @throws I18NRestException 400
     */
    public function patch(int $swimlane_id, int $column_id, CellPatchRepresentation $payload): void
    {
        $this->checkAccess();
        $patcher = CellPatcher::build();
        $patcher->patchCell($swimlane_id, $column_id, $payload);
    }
}
