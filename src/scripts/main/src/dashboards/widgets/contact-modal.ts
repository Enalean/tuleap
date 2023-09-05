/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import CKEDITOR from "ckeditor4";
import { config as ckeditor_config } from "@tuleap/ckeditor-config";
import { createModal } from "tlp";

document.addEventListener("DOMContentLoaded", function () {
    const massmail_project_member_links: NodeListOf<HTMLElement> = document.querySelectorAll(
        ".massmail-project-member-link",
    );

    massmail_project_member_links.forEach((massmail_project_member_link) => {
        massmail_project_member_link.addEventListener("click", function (event: Event): void {
            event.preventDefault();

            const project_id_element = document.getElementById(
                "massmail-project-members-project-id",
            );
            if (!(project_id_element instanceof HTMLInputElement)) {
                throw new Error("Massmail project id is undefined");
            }

            const project_id_data = massmail_project_member_link.dataset.projectId;
            if (!project_id_data) {
                throw new Error("Massmail project id dataset is undefined");
            }
            project_id_element.value = project_id_data;

            const massmail_project_member_element = document.getElementById(
                "massmail-project-members",
            );
            if (!massmail_project_member_element) {
                throw new Error("Massmail project member element is undefined");
            }

            const contact_modal = createModal(massmail_project_member_element);
            contact_modal.show();
        });
    });

    const textarea = document.getElementById("massmail-project-members-body");
    if (!(textarea instanceof HTMLTextAreaElement)) {
        return;
    }

    CKEDITOR.replace(textarea, ckeditor_config);
});
