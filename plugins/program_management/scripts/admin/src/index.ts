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

import { getPOFileFromLocale, initGettext } from "@tuleap/core/scripts/tuleap/gettext/gettext-init";
import { removeTeam } from "./teams/remove-team";
import { displayTeamsToAggregate } from "./teams/display-teams-to-aggregate";
import { addTeamInProgram } from "./teams/add-team";
import { initListPickersMilestoneSection } from "./milestones/init-list-pickers-milestone-section";
import { submitConfigurationHandler } from "./milestones/submit-configuration-handler";
import { TimeboxLabel } from "./dom/TimeboxLabel";
import { DocumentAdapter } from "./dom/DocumentAdapter";
import { initPreview } from "./milestones/preview-actualizer";

const PROGRAM_INCREMENT_LABEL_ID = "admin-configuration-program-increment-label-section";
const PROGRAM_INCREMENT_SUB_LABEL_ID = "admin-configuration-program-increment-sub-label-section";

document.addEventListener("DOMContentLoaded", async () => {
    const language = document.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }

    const gettext_provider = await initGettext(
        language,
        "program_management_admin",
        (locale) =>
            import(
                /* webpackChunkName: "program_management_admin-po-" */ "../po/" +
                    getPOFileFromLocale(locale)
            )
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

    const use_iteration_data = app.dataset.useIteration;
    if (use_iteration_data === undefined) {
        throw new Error("Use Iteration does not exist");
    }
    const use_iteration = Boolean(use_iteration_data);

    const retriever = new DocumentAdapter(document);
    const program_increment_label = TimeboxLabel.fromId(retriever, PROGRAM_INCREMENT_LABEL_ID);
    const program_increment_sub_label = TimeboxLabel.fromId(
        retriever,
        PROGRAM_INCREMENT_SUB_LABEL_ID
    );

    await displayTeamsToAggregate(gettext_provider, document);
    removeTeam(program_id);
    addTeamInProgram(program_id, document);
    await initListPickersMilestoneSection(document, gettext_provider, use_iteration);
    submitConfigurationHandler(document, gettext_provider, program_id, use_iteration);
    initPreview(retriever, gettext_provider, program_increment_label, program_increment_sub_label);
});
