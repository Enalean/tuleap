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

import { openAllTargetModalsOnClick } from "@tuleap/tlp-modal";
import { extractAggregatedTeamIds } from "../helper/aggregated-team-ids-extractor";
import { handleTeamRemove } from "../helper/button-to-remove-team-handler";

export function removeTeam(program_id: number): void {
    openAllTargetModalsOnClick(
        document,
        ".program-management-admin-remove-teams-open-modal-button",
    );

    const aggregated_team_ids = extractAggregatedTeamIds(document);

    const buttons_confirm_remove_team = document.getElementsByClassName(
        "program-management-remove-team-button",
    );

    for (const button_confirm_remove_team of buttons_confirm_remove_team) {
        if (!(button_confirm_remove_team instanceof HTMLElement)) {
            throw new Error("No button to confirm remove team");
        }

        handleTeamRemove(button_confirm_remove_team, aggregated_team_ids, program_id);
    }
}
