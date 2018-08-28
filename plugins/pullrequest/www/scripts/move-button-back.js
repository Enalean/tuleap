/**
 * Copyright (c) Enalean SAS - 2016 - 2018. All rights reserved
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

document.addEventListener("DOMContentLoaded", () => {
    var plugin_git_reference = document.getElementsByClassName("git-repository-name");

    if (!plugin_git_reference.length) {
        return;
    }

    var plugin_git_reference_title = plugin_git_reference[0];
    var button_back = document.getElementsByClassName("pull-request-button-back")[0];

    if (!button_back) {
        return;
    }

    plugin_git_reference_title.appendChild(button_back);
});
