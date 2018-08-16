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

import { get } from "tlp";
import { sanitize } from "dompurify";
import { init as togglerInit } from "../tuleap/toggler.js";
import { loadTooltips } from "../codendi/Tooltip.js";

export default async function init() {
    const widgets = document.querySelectorAll(".dashboard-widget-asynchronous");

    for (const widget of widgets) {
        const response = await get(widget.dataset.ajaxUrl);
        const html = await response.text();

        widget.innerHTML = sanitize(html);
        widget.classList.remove("dashboard-widget-asynchronous-loading");
        loadTooltips();
        togglerInit(widget);
    }
}
