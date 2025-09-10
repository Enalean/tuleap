/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import { getJSON, uri } from "@tuleap/fetch-result";

document.addEventListener("DOMContentLoaded", () => {
    initAccessControlsVersionDisplayer();
    toggleSvnHelp();

    function initAccessControlsVersionDisplayer() {
        const version_selected = document.getElementById("version-selected");
        const version_displayer = document.getElementById("other-version-content");

        if (version_selected === null || version_displayer === null) {
            return;
        }

        const project_id = document.querySelector("input[name=project_id]").value;
        const repo_id = document.querySelector("input[name=repo_id]").value;

        function updateVersionDisplayer(response) {
            let text_to_display = version_displayer.dataset.emptyVersion;
            document.getElementById("old-access-file-form").style.visibility = "visible";

            if (response.content === "") {
                version_displayer.classList.add("empty-version");
                version_displayer.setAttribute("disabled", "");
            } else {
                text_to_display = response.content;
                version_displayer.classList.remove("empty-version");
                version_displayer.removeAttribute("disabled");
            }

            version_displayer.innerText = text_to_display;
        }

        version_selected.addEventListener("change", () => {
            if (version_selected.value === "0") {
                version_displayer.innerText = "";
                version_displayer.setAttribute("disabled", "");
                document.getElementById("old-access-file-form").visibility = "hidden";
            } else {
                getJSON(
                    uri`/plugins/svn/?action=display-archived-version&accessfile_history_id=${version_selected.value}&group_id=${project_id}&repo_id=${repo_id}`,
                ).andThen(updateVersionDisplayer);
            }
        });
    }

    function toggleSvnHelp() {
        const help = document.getElementById("toggle-svn-help");
        if (help === null) {
            return;
        }

        help.addEventListener("click", () => {
            document
                .getElementById("svn-repository-help")
                .classList.toggle("svn-repository-help-hidden");
        });
    }
});
