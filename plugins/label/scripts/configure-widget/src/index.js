/*
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

import "../themes/labels-box.scss";
import { create } from "./labels-box";

document.addEventListener("dashboard-edit-widget-modal-content-loaded", (event) =>
    initLabelsBox(event.detail.target),
);
document.addEventListener("dashboard-add-widget-settings-loaded", (event) =>
    initLabelsBox(event.detail.target),
);

function initLabelsBox(widget_container) {
    const container = widget_container.querySelector(".project-labels");
    if (!container) {
        return;
    }

    let selected_labels = [];

    for (const option of container.options) {
        selected_labels.push({
            id: option.value,
            text: option.dataset.name,
            is_outline: JSON.parse(option.dataset.isOutline),
            color: option.dataset.color,
        });
    }
    create(
        container,
        container.dataset.labelsEndpoint,
        selected_labels,
        container.dataset.placeholder,
    );
}
