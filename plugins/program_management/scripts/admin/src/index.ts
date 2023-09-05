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

import "../themes/admin.scss";
import { getPOFileFromLocaleWithoutExtension, initGettext } from "@tuleap/gettext";
import { removeTeam } from "./teams/remove-team";
import { displayTeamsToAggregate } from "./teams/display-teams-to-aggregate";
import { addTeamInProgram } from "./teams/add-team";
import { initListPickersMilestoneSection } from "./milestones/init-list-pickers-milestone-section";
import { submitConfigurationHandler } from "./milestones/submit-configuration-handler";
import { initPreviewTrackerLabels } from "./helper/init-preview-labels-helper";
import { DocumentAdapter } from "./dom/DocumentAdapter";
import { initSynchronizeTeamButtons } from "./teams/synchronize-team";

document.addEventListener("DOMContentLoaded", async () => {
    const language = document.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }

    const gettext_provider = await initGettext(
        language,
        "program_management_admin",
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const app = document.getElementById("program-management-administration");
    if (!app) {
        throw new Error("Program Management Administration does not exist");
    }
    const program_id_data = app.dataset.programId;
    if (!program_id_data) {
        throw new Error("Program id does not exist");
    }
    const program_id = Number.parseInt(program_id_data, 10);

    displayTeamsToAggregate(gettext_provider, document);
    removeTeam(program_id);
    addTeamInProgram(program_id, document);
    initListPickersMilestoneSection(document, gettext_provider);
    submitConfigurationHandler(document, gettext_provider, program_id);
    const adapter = new DocumentAdapter(document);
    initPreviewTrackerLabels(adapter, gettext_provider);
    initSynchronizeTeamButtons(adapter, window.location);
});
