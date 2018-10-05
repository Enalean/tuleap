/**
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

document.addEventListener("DOMContentLoaded", () => {
    moveWidgetIconsAtTheRightOfTheWidgetBecauseItIsVerticallySplitted();

    function moveWidgetIconsAtTheRightOfTheWidgetBecauseItIsVerticallySplitted() {
        const widget = document.querySelector(".dashboard-widget.projectmembers");
        if (!widget) {
            return;
        }

        const icons = widget.querySelector(".dashboard-widget-icons");
        if (!icons) {
            return;
        }

        const subtitle = widget.querySelector(".widget-project-members-administrators-title");
        if (!subtitle) {
            return;
        }

        subtitle.parentNode.appendChild(icons);
    }
});
