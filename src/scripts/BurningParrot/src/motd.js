/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

/**
 * I make sure that the sidebar and the main content are not hidden under the
 * top navbar whent the motd is displayed
 */

import { updateTopMarginAccordinglyToMOTDHeight } from "./update-ui-margins-according-to-motd.ts";

export { init };

function init() {
    if (document.querySelector(".motd")) {
        window.addEventListener("resize", updateTopMarginAccordinglyToMOTDHeight);
        updateTopMarginAccordinglyToMOTDHeight();
    }
}
