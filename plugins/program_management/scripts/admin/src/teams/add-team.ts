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
import { extractAggregatedTeamIds } from "../helper/aggregated-team-ids-extractor";
import { manageTeamOfProgram } from "../api/manage-team";
import { resetRestErrorAlert, setRestErrorMessage } from "../helper/rest-error-helper";
import { resetButtonToAddTeam, setButtonToDisabledWithSpinner } from "../helper/button-helper";
import type { ErrorRest } from "../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

const DISPLAY_ERROR_REST_ID = "program-management-add-team-error-rest";

export function addTeamInProgram(program_id: number, doc: Document): void {
    const button_to_add = doc.getElementById("program-management-add-team-button");

    if (!button_to_add || !(button_to_add instanceof HTMLButtonElement)) {
        throw new Error("Button to add team does not exist");
    }

    const aggregated_teams = extractAggregatedTeamIds(doc);

    button_to_add.addEventListener("click", async () => {
        resetRestErrorAlert(doc, DISPLAY_ERROR_REST_ID);

        const select = doc.getElementById("program-management-choose-teams");
        if (!select || !(select instanceof HTMLSelectElement)) {
            throw new Error("Select team does not exist");
        }

        const team_id_to_add = select.options[select.selectedIndex];
        if (!team_id_to_add.value) {
            return;
        }

        setButtonToDisabledWithSpinner(button_to_add);
        try {
            await manageTeamOfProgram({
                program_id,
                team_ids: [...aggregated_teams, Number.parseInt(team_id_to_add.value, 10)],
            });
            window.location.reload();
        } catch (e) {
            if (!(e instanceof FetchWrapperError)) {
                throw e;
            }
            e.response
                .json()
                .then(({ error }: ErrorRest) => {
                    let error_message = error.message;
                    if (error.i18n_error_message) {
                        error_message = error.i18n_error_message;
                    }
                    setRestErrorMessage(
                        doc,
                        DISPLAY_ERROR_REST_ID,
                        error.code + " " + error_message,
                    );
                })
                .catch(() => setRestErrorMessage(doc, DISPLAY_ERROR_REST_ID, "404 Error"));
        } finally {
            resetButtonToAddTeam(button_to_add);
        }
    });
}
