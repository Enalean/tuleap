/**
 * Copyright (c) Enalean SAS - 2017 - Present. All rights reserved
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

import { createDropdown } from "@tuleap/tlp-dropdown";

document.addEventListener("DOMContentLoaded", () => {
    const dropdown_triggers = document.querySelectorAll(
        ".siteadmin-projectlist-result-project-more"
    );

    [].forEach.call(dropdown_triggers, (dropdown_trigger) => {
        createDropdown(dropdown_trigger);
    });
});
