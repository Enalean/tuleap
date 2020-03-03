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

import { modal as createModal } from "tlp";
import { loadAvatarReset } from "./avatar/reset";
import { loadAvatarPreview } from "./avatar/preview";

const button = document.getElementById("account-information-avatar-button");

if (!(button instanceof HTMLButtonElement)) {
    throw new Error("#account-information-avatar not found or is not a button");
}

if (!button.dataset.targetModalId) {
    throw new Error("Button must have a data-target-modal-id");
}

const modal_element = document.getElementById(button.dataset.targetModalId);
if (modal_element === null) {
    throw new Error("Target modal element is not found");
}

const modal = createModal(modal_element);

button.addEventListener("click", () => {
    modal.show();
});

loadAvatarReset();
loadAvatarPreview();
