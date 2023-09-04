/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import { getPreviewContainer } from "./preview-container";
import { useDefaultAvatar } from "./use-default";

export function loadAvatarReset(): void {
    const btn = document.getElementById("account-information-avatar-modal-use-default-button");
    if (!(btn instanceof HTMLButtonElement)) {
        throw Error(
            "#account-information-avatar-modal-use-default-button not found or not a button",
        );
    }

    btn.addEventListener("click", function () {
        const preview_container = getPreviewContainer();

        preview_container.classList.remove("account-information-avatar-modal-preview");
        const img = preview_container.querySelector("img");
        if (img) {
            const data_url = btn.dataset.defaultAvatarDataUrl;
            if (data_url) {
                img.src = data_url;
            } else {
                img.remove();
            }
        }

        const use_default_avatar = document.getElementById(
            "account-information-avatar-modal-use-default",
        );
        if (!(use_default_avatar instanceof HTMLInputElement)) {
            throw Error("#account-information-avatar-modal-use-default not found");
        }

        if (!btn.form) {
            throw Error("Unable to find the form of the input");
        }

        btn.form.reset();
        useDefaultAvatar("1");
    });
}
