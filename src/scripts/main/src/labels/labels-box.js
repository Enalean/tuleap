/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { recursiveGet, patch } from "@tuleap/tlp-fetch";
import { select2 } from "tlp";
import mustache from "mustache";

export async function create(
    container,
    labels_endpoint,
    available_labels_endpoint,
    is_update_allowed,
    input_placeholder,
) {
    const existing_labels = await recursiveGet(labels_endpoint, {
        params: {
            limit: 50,
        },
        getCollectionCallback: ({ labels }) => labels.map(convertLabelToSelect2Entry),
    });

    initiateSelect2(
        container,
        existing_labels,
        labels_endpoint,
        available_labels_endpoint,
        is_update_allowed,
        input_placeholder,
    );
}

const convertLabelToSelect2Entry = ({ id, label, is_outline, color }) => ({
    id,
    text: label,
    is_outline,
    color,
});

function initiateSelect2(
    container,
    existing_labels,
    labels_endpoint,
    available_labels_endpoint,
    is_update_allowed,
    placeholder,
) {
    existing_labels.forEach((label) => {
        const option = createOption(label);
        container.append(option);
    });

    select2(container, {
        disabled: !is_update_allowed,
        tags: true,
        multiple: true,
        placeholder,
        containerCssClass: "item-labels-box-select2",
        dropdownCssClass: "item-labels-box-select2-results",
        templateResult: formatLabel,
        templateSelection: formatLabelSelected,
        width: "100%",
        escapeMarkup: function (markup) {
            return markup;
        },
        ajax: {
            url: available_labels_endpoint,
            dataType: "json",
            delay: 250,
            cache: true,
            data: (data) => ({ query: data.term }),
            processResults: (data) => ({ results: data.labels.map(convertLabelToSelect2Entry) }),
        },
    })
        .on("select2:unselect", (event) => {
            removeLabel(labels_endpoint, event.params.data);
        })
        .on("select2:select", (event) => {
            addLabel(labels_endpoint, event.params.data);
        });
}

const createOption = ({ id, text, color, is_outline }) => {
    const option = new Option(text, id, true, true);
    option.dataset.color = color;
    option.dataset.isOutline = is_outline;

    return option;
};

function formatLabel(label, li_element) {
    if (label.color) {
        const bullet_class = label.is_outline ? "far fa-circle" : "fa fa-circle";
        li_element.classList.add(`select-item-label-color-${label.color}`);

        return mustache.render(
            '<span class="select-item-label-title"><i class="select-item-label-bullet {{ bullet_class }}"></i>{{ label }}</span>',
            { bullet_class: bullet_class, label: label.text },
        );
    }

    return mustache.render('<span class="select-item-label-title">{{ label }}</span>', {
        label: label.text,
    });
}

function formatLabelSelected(label, li_elements) {
    const color = getColor(label),
        is_outline = getIsOutline(label),
        li_element = li_elements[0];

    li_element.classList.add(`select-item-label-color-${color}`);

    if (is_outline) {
        li_element.classList.add("select-item-label-outline");
    }
    return mustache.render("<span>{{ label }}</span>", { label: label.text });
}

function getColor(label) {
    let color = "";

    if (label.color) {
        color = label.color;
    } else if (label.element) {
        color = label.element.dataset.color;
    }

    return color;
}

function getIsOutline(label) {
    let is_outline = false;

    if (label.is_outline) {
        is_outline = label.is_outline;
    } else if (label.element && label.element.dataset && label.element.dataset.isOutline) {
        is_outline = JSON.parse(label.element.dataset.isOutline);
    }

    return is_outline;
}

function removeLabel(labels_endpoint, { id }) {
    const label_id = parseInt(id, 10);

    if (!label_id) {
        return;
    }

    const label = { id: label_id };

    patch(labels_endpoint, {
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ remove: [label] }),
    });
}

function addLabel(labels_endpoint, { id, text }) {
    const label_id = parseInt(id, 10);
    const label = label_id ? { id: label_id } : { label: text };

    return patch(labels_endpoint, {
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ add: [label] }),
    });
}
