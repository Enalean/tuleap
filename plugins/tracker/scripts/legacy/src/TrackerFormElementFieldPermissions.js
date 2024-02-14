/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 *
 * Originally written by Nicolas Terray, 2008
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

document.addEventListener("DOMContentLoaded", function () {
    function init() {
        const elements = document.getElementsByClassName("tracker_field_permissionsonartifact");
        for (const element of elements) {
            const field_id = element.dataset.fieldId;
            const select_ugroups = document.getElementById(`artifact_${field_id}_perms_ugroups`);
            const checkbox_use_perms = document.getElementById(
                `artifact_${field_id}_use_artifact_permissions`,
            );
            if (select_ugroups === null || checkbox_use_perms === null) {
                continue;
            }
            disablePermissionsFieldIfNeeded(checkbox_use_perms, select_ugroups);
            bindSwitch(checkbox_use_perms, select_ugroups);
            const checkbox_activate_masschange_update = document.getElementById(
                `artifact_${field_id}_perms_mass_update`,
            );
            if (checkbox_activate_masschange_update) {
                checkbox_use_perms.disabled = true;
                checkbox_activate_masschange_update.addEventListener("change", () => {
                    checkbox_use_perms.disabled = !checkbox_activate_masschange_update.checked;
                });
            }
        }
    }

    function disablePermissionsFieldIfNeeded(checkbox_use_perms, select_ugroups) {
        if (!checkbox_use_perms.checked) {
            select_ugroups.disabled = true;
        }
    }

    function bindSwitch(checkbox_use_perms, select_ugroups) {
        checkbox_use_perms.addEventListener("change", () => {
            select_ugroups.disabled = !checkbox_use_perms.checked;
        });
    }

    init();
});
