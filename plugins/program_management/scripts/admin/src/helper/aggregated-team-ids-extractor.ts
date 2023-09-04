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

export function extractAggregatedTeamIds(doc: Document): number[] {
    const buttons_open_modal_to_remove_team = doc.getElementsByClassName(
        "program-management-admin-remove-teams-open-modal-button",
    );

    const team_ids: number[] = [];

    for (const button_open_modal_to_remove_team of buttons_open_modal_to_remove_team) {
        if (!(button_open_modal_to_remove_team instanceof HTMLElement)) {
            throw new Error("Button is not HTMLElement");
        }

        button_open_modal_to_remove_team.addEventListener("click", (event) => {
            event.preventDefault();
        });

        const team_id = button_open_modal_to_remove_team.dataset.teamId;
        if (!team_id) {
            throw new Error("No team id on button");
        }

        team_ids.push(Number.parseInt(team_id, 10));
    }

    return team_ids;
}
