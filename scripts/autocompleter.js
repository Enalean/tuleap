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

import { select2 } from "tlp";
import { render } from "mustache";

document.addEventListener("DOMContentLoaded", () => {
    const select_bot = document.getElementById("select_bot"),
        inputs_channels = document.getElementById("channels");

    if (select_bot) {
        select2(select_bot, {
            minimumResultsForSearch: -1,
            escapeMarkup: function (markup) {
                return markup;
            },
            templateResult: formatBot,
            templateSelection: formatBot,
        });
    }

    if (inputs_channels) {
        const selected_channels = [];
        for (const option of inputs_channels.options) {
            selected_channels.push({
                id: option.value,
                text: option.text,
            });
        }

        select2(inputs_channels, {
            tokenSeparators: [",", " "],
            placeholder: inputs_channels.dataset.placeholder,
            tags: [],
            minimumResultsForSearch: Infinity,
            initSelection: (container, callback) => callback(selected_channels),
        });
    }

    function formatBot(item) {
        if (item.element) {
            const src_image = item.element.dataset.image,
                label = item.text;

            if (src_image) {
                const format_item =
                    '<img src="{{ src_image }}" class="tlp-avatar-mini"> {{ label }}';

                return render(format_item, { src_image, label });
            }
        }

        return render("{{ text }}", { text: item.text });
    }
});
