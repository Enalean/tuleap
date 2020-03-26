/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

var select2;
var escaper;
if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    select2 = require("tlp").select2;
    escaper = require("./escaper.js").escaper;

    module.exports = {
        autocomplete_projects_for_select2: autocomplete_projects_for_select2,
        autocomplete_users_for_select2: autocomplete_users_for_select2,
    };
} else {
    var tuleap = window.tuleap || {};

    select2 = window.tlp.select2;
    escaper = tuleap.escaper;
    tuleap.autocomplete_projects_for_select2 = autocomplete_projects_for_select2;
    tuleap.autocomplete_users_for_select2 = autocomplete_users_for_select2;
}

function autocomplete_projects_for_select2(element, options) {
    options = options || {};

    options.include_private_projects = options.include_private_projects || 0;
    options.placeholder = element.dataset.placeholder || "";
    options.minimumInputLength = 3;
    options.allowClear = true;
    options.debug = true;
    options.ajax = {
        url: "/project/autocomplete.php",
        dataType: "json",
        delay: 250,
        data: function (params) {
            return {
                return_type: "json_for_select_2",
                name: params.term,
                page: params.page || 1,
                private: options.include_private_projects ? 1 : 0,
            };
        },
    };

    select2(element, options);
}

const convertUsersToSelect2Entry = ({ tuleap_user_id, text }) => ({
    id: tuleap_user_id,
    text: text,
});

function autocomplete_users_for_select2(element, options) {
    options = options || {};

    options.use_tuleap_id = options.use_tuleap_id || false;
    options.internal_users_only = options.internal_users_only || 0;
    options.placeholder = element.dataset.placeholder || "";
    options.minimumInputLength = 3;
    options.allowClear = true;

    options.ajax = {
        url: "/user/autocomplete.php",
        dataType: "json",
        delay: 250,
        data: function (params) {
            return {
                return_type: "json_for_select_2",
                name: params.term,
                page: params.page || 1,
                codendi_user_only: options.internal_users_only,
                project_id: options.project_id || "",
                user: options.user,
            };
        },
    };

    if (options.use_tuleap_id === true) {
        options.ajax.processResults = (data) => ({
            results: data.results.map(convertUsersToSelect2Entry),
        });
    }
    options.escapeMarkup = function (markup) {
        return markup;
    };
    options.templateResult = formatUser;
    options.templateSelection = formatUserWhenSelected;

    return select2(element, options);

    function formatUser(user) {
        if (user.loading) {
            return escaper.html(user.text);
        }

        /* eslint-disable no-multi-str */
        var markup =
            '<div class="select2-result-user"> \
            <div class="tlp-avatar select2-result-user__avatar"> \
                ' +
            (user.has_avatar ? '<img src="' + escaper.html(user.avatar_url) + '">' : "") +
            " \
            </div> \
            " +
            escaper.html(user.text) +
            " \
        </div>";
        /* eslint-enable no-multi-str */

        return markup;
    }

    function formatUserWhenSelected(user) {
        return escaper.html(user.text);
    }
}
