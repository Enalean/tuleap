/*
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

/* global CKEDITOR:readonly tlp:readonly tuleap:readonly */

document.addEventListener("DOMContentLoaded", function () {
    var massmail_project_member_links = document.querySelectorAll(".massmail-project-member-link");

    [].forEach.call(massmail_project_member_links, function (massmail_project_member_link) {
        massmail_project_member_link.addEventListener("click", function () {
            document.getElementById("massmail-project-members-project-id").value =
                massmail_project_member_link.dataset.projectId;

            var contact_modal = tlp.modal(document.getElementById("massmail-project-members"));
            contact_modal.show();
        });
    });

    var textarea = document.getElementById("massmail-project-members-body");
    if (!textarea) {
        return;
    }

    CKEDITOR.replace(textarea, {
        toolbar: tuleap.ckeditor.toolbar,
    });
});
