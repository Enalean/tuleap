/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

export function moveBreadCrumbs() {
    window.setTimeout(function() {
        const origin = document.getElementById("testmanagement-breadcrumb");
        if (!origin) {
            return;
        }

        const main = document.querySelector("main");
        if (!main) {
            return;
        }

        main.insertBefore(origin, main.firstElementChild);
        origin.removeAttribute("id");

        const breadcrumbs = origin.querySelectorAll(".breadcrumb-item");
        if (breadcrumbs.length === 0) {
            origin.remove();
        }
    }, 0);
}
