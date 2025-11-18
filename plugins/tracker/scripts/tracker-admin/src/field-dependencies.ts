/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

const HIDDEN_CLASS = "field-dependencies-icon-hidden";

document.addEventListener("DOMContentLoaded", function () {
    document
        .querySelectorAll<HTMLInputElement>(".tracker-field-dependencies-checkbox")
        .forEach((checkbox) => {
            checkbox.addEventListener("change", function () {
                const label = checkbox.closest("label");
                const checked_icon = label?.querySelector(".field-dependencies-turn-up");
                const unchecked_icon = label?.querySelector(".field-dependencies-bullet-point");

                if (!checked_icon || !unchecked_icon) {
                    return;
                }

                if (checkbox.checked) {
                    checked_icon.classList.remove(HIDDEN_CLASS);
                    unchecked_icon.classList.add(HIDDEN_CLASS);
                } else {
                    unchecked_icon.classList.remove(HIDDEN_CLASS);
                    checked_icon.classList.add(HIDDEN_CLASS);
                }
            });
        });
});
