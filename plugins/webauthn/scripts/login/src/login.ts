/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import "../themes/style.scss";
import { selectOrThrow } from "@tuleap/dom";

document.addEventListener("DOMContentLoaded", () => {
    const button = selectOrThrow(document, "#webauthn-button", HTMLButtonElement);
    const input = selectOrThrow(document, "#webauthn-username", HTMLInputElement);
    const form = selectOrThrow(document, "#webauthn-form", HTMLFormElement);

    form.addEventListener("submit", (event) => {
        event.preventDefault();
    });

    input.addEventListener("input", () => {
        button.disabled = input.value.length === 0;
    });
});
