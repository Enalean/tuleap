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

import { manageTeamOfProgram } from "../api/manage-team";

export function handleTeamRemove(
    button_confirm_remove_team: HTMLElement,
    aggregated_team_ids: number[],
    program_id: number,
): void {
    const team_id_to_remove = button_confirm_remove_team.dataset.teamId;
    if (!team_id_to_remove) {
        throw new Error("No team id on button");
    }

    button_confirm_remove_team.addEventListener("click", async () => {
        const new_team_ids_of_program = aggregated_team_ids.filter(
            (id) => id !== Number.parseInt(team_id_to_remove, 10),
        );

        await manageTeamOfProgram({
            team_ids: new_team_ids_of_program,
            program_id,
        });

        window.location.reload();
    });
}
