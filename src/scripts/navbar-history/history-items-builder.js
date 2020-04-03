/**
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

import { render } from "mustache";
import { sanitize } from "dompurify";

export default function buildHistoryItems(entries, history_content) {
    const template = document.getElementById("history-item-placeholder").textContent;
    const rendered_history = render(template, { entries: normalize(entries) });

    insertRenderedHistoryInDOM(rendered_history, history_content);
    addHistoryItemListeners(history_content);
}

function normalize(entries) {
    const all_underscores = /_/g;

    entries.forEach(function (entry) {
        entry.color_name = entry.color_name.replace(all_underscores, "-");
        entry.has_quick_links = entry.quick_links.length > 0;
    });

    return entries;
}

function insertRenderedHistoryInDOM(rendered_history, history_content) {
    const purified_history = sanitize(rendered_history, { RETURN_DOM_FRAGMENT: true });

    history_content.appendChild(purified_history);
}

function addHistoryItemListeners(history_content) {
    const extra_links = history_content.querySelectorAll(".history-item-extra-link");
    [].forEach.call(extra_links, (extra_link) => {
        extra_link.addEventListener("click", (event) => {
            event.stopPropagation();
            event.preventDefault();
            window.location.href = extra_link.dataset.href;
        });
    });
}
