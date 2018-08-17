/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

import { get, put } from "tlp";
import initNavbarHistoryController from "./navbar-history-controller.js";

export function init() {
    const user_history_dropdown_trigger = document.querySelector(
        "#nav-dropdown-user-history > .nav-dropdown-link"
    );

    if (!user_history_dropdown_trigger) {
        return;
    }

    initNavbarHistoryController(get, put, user_history_dropdown_trigger);
}
