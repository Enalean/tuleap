/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 */

export function initProjectWidgetsConfigurationFormSubmission(mount_point: Document): void {
    const switches = mount_point.querySelectorAll(".tlp-switch-checkbox");

    [].forEach.call(switches, function (switch_button: HTMLFormElement) {
        switch_button.addEventListener("change", function () {
            if (!switch_button.dataset.formId) {
                return;
            }
            const form = mount_point.getElementById(switch_button.dataset.formId);
            if (!(form instanceof HTMLFormElement)) {
                throw new Error("Retrieved element is not a form");
            }
            form.submit();
        });
    });
}
