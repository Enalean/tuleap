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

document.addEventListener("DOMContentLoaded", () => {
    toggleSvnHelp();
    moveViewVCBreadcrumbsIntoTuleapBreadcrumbs();
    expandFileRevisionMessage();

    function toggleSvnHelp(): void {
        const button = document.getElementById("toggle-svn-help");
        if (button === null) {
            return;
        }

        button.addEventListener("click", () => {
            const help = document.getElementById("svn-repository-help");
            if (help) {
                help.hidden = !help.hidden;
            }
        });
    }

    function expandFileRevisionMessage(): void {
        const element = document.querySelector("#tuleap-viewvc-file-revision-message");
        if (!element) {
            return;
        }

        const message = element.innerHTML.trim().split(/\n/);
        if (message.length < 2) {
            return;
        }

        const [first, ...remaining] = message;

        const button = document.createElement("button");
        button.type = "button";
        button.id = "tuleap-viewvc-file-revision-message-button";
        button.classList.add("tlp-button-mini", "tlp-button-secondary", "tlp-button-outline");
        button.textContent = "Expand";

        const description = document.createElement("div");
        description.id = "tuleap-viewvc-file-revision-message-description";
        description.textContent = remaining.join("\n");

        element.innerHTML = "";
        element.append(first, button, description);

        button.addEventListener("click", function () {
            element.classList.add("tuleap-viewvc-file-revision-message--expand");
            this.style.display = "none";
        });
    }

    function moveViewVCBreadcrumbsIntoTuleapBreadcrumbs(): void {
        const tuleap_breadcrumbs = document.querySelector("main .breadcrumb");
        if (!tuleap_breadcrumbs) {
            return;
        }

        document
            .querySelectorAll("#tuleap-viewvc-header-breadcrumbs > .breadcrumb-item")
            .forEach((step) => {
                tuleap_breadcrumbs.appendChild(step);
            });
    }
});
