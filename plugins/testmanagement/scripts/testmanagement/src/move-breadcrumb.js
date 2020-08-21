/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

export function moveBreadCrumbs(project_public_name, project_url) {
    window.setTimeout(function () {
        const origin = document.getElementById("testmanagement-breadcrumb");
        if (!origin) {
            return;
        }

        const project_item = document.createElement("span");
        project_item.classList.add("breadcrumb-item");
        project_item.classList.add("breadcrumb-project");

        const project_link = document.createElement("a");
        project_link.classList.add("breadcrumb-link");
        project_link.href = project_url;
        project_link.textContent = project_public_name;

        project_item.appendChild(project_link);
        origin.insertBefore(project_item, origin.firstChild);

        const main = document.querySelector("main");
        if (!main) {
            return;
        }

        const toolbar = document.querySelector("nav.toolbar");
        if (!toolbar) {
            return;
        }

        main.insertBefore(origin, toolbar);
        origin.removeAttribute("id");

        const breadcrumbs = origin.querySelectorAll(".breadcrumb-item");
        if (breadcrumbs.length === 0) {
            origin.remove();
        }
    }, 0);
}
