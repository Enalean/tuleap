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

/* global require:readonly module:readonly */

var escaper;
var jQuery;
if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    escaper = require("./escaper").escaper;
    jQuery = require("jquery");

    module.exports = {
        loadUserAndUgroupAutocompleter: loadUserAndUgroupAutocompleter,
        addDataToAutocompleter: addDataToAutocompleter,
        enableAutocompleter: enableAutocompleter,
        resetPlaceholder: resetPlaceholder,
    };
} else {
    var tuleap = window.tuleap || {};

    escaper = tuleap.escaper;
    jQuery = window.jQuery;

    tuleap.addDataToAutocompleter = addDataToAutocompleter;
    tuleap.enableAutocompleter = enableAutocompleter;
    tuleap.resetPlaceholder = resetPlaceholder;
    tuleap.loadUserAndUgroupAutocompleter = loadUserAndUgroupAutocompleter;
}

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

    return (
        /*eslint-disable no-multi-str */
        '<div class="avatar autocompleter-avatar"> \
            ' +
        (user.has_avatar ? '<img src="' + escaper.html(user.avatar_url) + '">' : "") +
        " \
        </div> \
        " +
        escaper.html(user.text)
        /*eslint-enable no-multi-str */
    );
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

function addDataToAutocompleter(input, items) {
    jQuery(input).select2("data", items);
}

function enableAutocompleter(input) {
    jQuery(input).select2("enable", true);
}

function resetPlaceholder(input) {
    jQuery(input).select2("val", null);
}

function loadUserAndUgroupAutocompleter(input) {
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
