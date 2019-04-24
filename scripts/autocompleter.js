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
(function($) {
    "use strict";

    document.addEventListener("DOMContentLoaded", function() {
        var select_bot = document.querySelector("#select_bot");
        var inputs_channels = document.querySelectorAll(".input_channels");

        if (select_bot) {
            loadBotSelectList(select_bot);
        }

        if (inputs_channels.length == 0) {
            return;
        } else {
            [].forEach.call(inputs_channels, function(input) {
                loadChannelAutoCompleter(input);
            });
        }
    });

    function loadChannelAutoCompleter(input) {
        $(input).select2({
            width: "100%",
            tokenSeparators: [",", " "],
            placeholder: input.dataset.placeholder,
            tags: [],
            initSelection: function(element, callback) {
                var data = [];
                $(element.val().split(", ")).each(function() {
                    data.push({ id: this, text: this });
                });
                callback(data);
            }
        });
    }

    function loadBotSelectList(input) {
        $(input).select2({
            width: "50%",
            placeholder: "Select a bot",
            minimumResultsForSearch: Infinity,
            formatResult: formatItem,
            formatSelection: formatItem
        });

        function formatItem(item) {
            var src_image = $(item.element).data("image");
            var format_item = tuleap.escaper.html(item.text);

            if (src_image) {
                format_item =
                    '<img src="' +
                    src_image +
                    '" class="img-circle" width="20" height="20"/> ' +
                    format_item;
            }

            return format_item;
        }
    }
})(jQuery);
