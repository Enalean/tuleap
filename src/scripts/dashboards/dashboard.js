/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import modalInit from "./dashboard-modals";
import dropdownInit from "./dashboard-dropdowns";
import asyncWidgetInit from "./dashboard-async-widget";
import minimizeInit from "./dashboard-minimize";
import dragDropInit from "./dashboard-drag-drop.js";
import loadTogglers from "./dashboard-load-togglers";
import { loadTooltips } from "@tuleap/tooltip";

document.addEventListener("DOMContentLoaded", async function () {
    modalInit();
    dropdownInit();
    dragDropInit();
    await asyncWidgetInit();
    minimizeInit();
    loadTogglers();
    loadTooltips();
});
