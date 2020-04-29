/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { escaper } from "./escaper";
import jQuery from "jquery";

function formatItem(item) {
    var type = item.type ? item.type : "other";

    if (type === "group") {
        return '<i class="fa fa-users autocompleter-icon-group"></i>' + escaper.html(item.text);
    } else if (type === "user") {
        return formatUser(item);
    } else {
        return escaper.html(item.text);
    }
}

function formatUser(user) {
    if (user.loading) {
        return escaper.html(user.text);
    }

    const avatar = user.has_avatar ? '<img src="' + escaper.html(user.avatar_url) + '">' : "";
    const escaped_text = escaper.html(user.text);
    return `<div class="avatar autocompleter-avatar">${avatar}</div>${escaped_text}`;
}

function createSearchChoice(term, data) {
    var data_that_matches_term = jQuery(data).filter(function () {
        return this.text == term;
    });

    if (data_that_matches_term.length === 0) {
        return {
            id: term,
            text: term,
        };
    }
}

export function addDataToAutocompleter(input, items) {
    jQuery(input).select2("data", items);
}

export function enableAutocompleter(input) {
    jQuery(input).select2("enable", true);
}

export function resetPlaceholder(input) {
    jQuery(input).select2("val", null);
}

export function loadUserAndUgroupAutocompleter(input) {
    if (!input) {
        return;
    }

    jQuery(input).select2({
        width: "100%",
        dropdownCssClass: "autocompleter-users-and-ugroups-dropdown",
        tags: true,
        multiple: true,
        tokenSeparators: [",", " "],
        minimumInputLength: 3,
        placeholder: input.dataset.placeholder,
        ajax: {
            url: "/user/autocomplete.php",
            dataType: "json",
            quietMillis: 250,
            data: function (term) {
                return {
                    return_type: "json_for_select_2",
                    "with-groups-of-user-in-project-id": input.dataset.projectId,
                    "additional-information": input.dataset.additionalInfo,
                    name: term,
                };
            },
            results: function (data) {
                return {
                    results: data.results,
                };
            },
        },
        createSearchChoice: createSearchChoice,
        formatResult: formatItem,
        formatSelection: formatItem,
        escapeMarkup: function (m) {
            return m;
        },
    });
}
