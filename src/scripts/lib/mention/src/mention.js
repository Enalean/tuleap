/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

import jQuery from "jquery";
import "../vendor/at/js/caret.min.js";
import "../vendor/at/js/atwho.min.js";
import "../vendor/at/css/atwho.min.css";
import { escaper } from "@tuleap/html-escaper";

/**
 * Handle @user
 */
export function initMentions(selector) {
    jQuery(selector).atwho({
        at: "@",
        /* eslint-disable no-template-curly-in-string */
        tpl: '<li data-value="${atwho-at}${username}"><img class="user-avatar" src="${avatar_url}"> ${real_name} (${username})</li>',
        /* eslint-enable no-template-curly-in-string */
        callbacks: {
            remote_filter: function (query, callback) {
                if (query.length > 2) {
                    jQuery.getJSON("/api/v1/users", { query: query }, function (data) {
                        let minimal_and_html_sanitized_user_representation = [];
                        data.forEach(function (user) {
                            minimal_and_html_sanitized_user_representation.push({
                                username: escaper.html(user.username),
                                real_name: escaper.html(user.real_name),
                                avatar_url: escaper.html(user.avatar_url),
                            });
                        });

                        callback(minimal_and_html_sanitized_user_representation);
                    });
                }
            },
            sorter: function (query, items) {
                if (!query) {
                    return items;
                }

                return items.sort(function (a, b) {
                    return a.atwho_order - b.atwho_order;
                });
            },
        },
    });
}
jQuery(document).ready(function () {
    initMentions('input[type="text"].user-mention, textarea.user-mention');
});
