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

import { get, patch, select2 } from 'tlp';
import { sanitize } from 'dompurify';

export async function create(container, labels_endpoint, selected_labels) {
    initiateSelect2(container, selected_labels, labels_endpoint);
}

const convertLabelToSelect2Entry = ({ id, label, is_outline, color }) => ({ id, text: label, is_outline, color });

function initiateSelect2(container, selected_labels, labels_endpoint) {
    const options = {
        multiple         : true,
        allowClear       : true,
        initSelection    : (container, callback) => callback(selected_labels),
        templateResult   : formatLabel,
        templateSelection: formatLabelWhenSelected,
        escapeMarkup     : function (markup) { return markup; },
        ajax             : {
            url           : labels_endpoint,
            dataType      : 'json',
            delay         : 250,
            data          : data => ({ query: data.term }),
            processResults: data => ({ results: data.labels.map(convertLabelToSelect2Entry) })
        }
    };

    select2(container, options);
}

function formatLabel(label) {
    const escaped_text = sanitize(label.text);
    return `<span class="select-item-label-title">
                    <i class="select-item-label-bullet"></i>${escaped_text}
            </span>`;
}

function formatLabelWhenSelected(label) {
    return sanitize(label.text);
}