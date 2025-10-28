/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

export function initProjectDestinationSelection(
    project_select_box: HTMLSelectElement,
    fork_path_input: HTMLInputElement,
    project_fork_radio_button: HTMLInputElement,
    personal_fork_radio_button: HTMLInputElement,
): void {
    const enableForkDestinationProjectSelectBox = (): void => {
        project_select_box.disabled = false;
        fork_path_input.disabled = true;
    };

    const disableForkDestinationProjectSelectBox = (): void => {
        project_select_box.disabled = true;
        fork_path_input.disabled = false;
    };

    project_fork_radio_button.addEventListener("click", enableForkDestinationProjectSelectBox);
    personal_fork_radio_button.addEventListener("click", disableForkDestinationProjectSelectBox);
}
