/*
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

import {
    openAllTargetModalsOnClick,
    openTargetModalIdOnClick,
} from "@tuleap/core/scripts/tuleap/modals/modal-opener";

document.addEventListener("DOMContentLoaded", () => {
    initAddModal();
    initConfigurationModal();
    document
        .querySelectorAll(".mirror-show-repositories")
        .forEach((element): void =>
            element.addEventListener("click", (event): void => event.preventDefault())
        );
});

function initAddModal(): void {
    openTargetModalIdOnClick(document, "button-modal-add-mirror");
}

function initConfigurationModal(): void {
    openAllTargetModalsOnClick(
        document,
        "#button-modal-mirror-configuration, .mirror-show-repositories, .mirror-action-edit-button, [data-delete-mirror-button]"
    );
}
