/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { patch } from "tlp";

export async function moveElementFromProgramIncrementToTopBackLog(
    project_id: number,
    element_id: number
): Promise<void> {
    await patch(`/api/projects/${project_id}/program_backlog`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            add: [{ id: element_id }],
            remove: [],
            remove_from_program_increment_to_add_to_the_backlog: true,
        }),
    });
}
