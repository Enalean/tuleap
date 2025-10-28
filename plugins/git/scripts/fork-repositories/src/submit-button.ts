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

export function initSubmitButton(
    form: HTMLFormElement,
    submit_button: HTMLButtonElement,
    forkable_repositories: HTMLSelectElement,
): void {
    const disableSubmitButton = (): void => {
        submit_button.disabled = true;
    };

    forkable_repositories.addEventListener("change", (): void => {
        if (forkable_repositories.selectedOptions.length === 0) {
            disableSubmitButton();
            return;
        }

        submit_button.disabled = false;
    });
    form.addEventListener("submit", disableSubmitButton);
}
