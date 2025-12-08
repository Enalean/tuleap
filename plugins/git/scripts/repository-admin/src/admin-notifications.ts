/*
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

import "../styles/notifications.scss";
import { openAllTargetModalsOnClick } from "@tuleap/tlp-modal";
import { getLocaleWithDefault } from "@tuleap/locale";
import type { LocaleString } from "@tuleap/locale";
import type { User } from "@tuleap/core-rest-api-types";
import { createLazybox } from "@tuleap/lazybox";
import { initUsersAutocompleter } from "@tuleap/lazybox-users-autocomplete";
import { getAttributeOrThrow } from "@tuleap/dom";

document.addEventListener("DOMContentLoaded", () => {
    const locale = getLocaleWithDefault(document);
    openAllTargetModalsOnClick(document, "[data-modal-trigger]");
    instantiateUserAutocompleter(locale);
    listenToClickOnDeleteToAdjustDeletionModalInputParameters();
});
function instantiateUserAutocompleter(locale: LocaleString): void {
    document.querySelectorAll(".git-monitor-autocompleter-user").forEach((container) => {
        if (!(container instanceof HTMLElement)) {
            return;
        }
        const lazybox = createLazybox(document);
        lazybox.id = getAttributeOrThrow(container, "data-id");
        container.appendChild(lazybox);
        const hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = getAttributeOrThrow(container, "data-name");
        container.appendChild(hidden);
        initUsersAutocompleter(
            lazybox,
            [],
            (selected_users: ReadonlyArray<User>): void => {
                hidden.value = selected_users.map((user: User): number => user.id).join(",");
            },
            locale,
        );
    });
}

function listenToClickOnDeleteToAdjustDeletionModalInputParameters(): void {
    const deletion_parameter_input = document.getElementById("deletion_parameter");

    document.querySelectorAll("[data-deletion-parameter-name]").forEach((button) => {
        if (!(button instanceof HTMLElement)) {
            return;
        }

        button.addEventListener("click", () => {
            if (!(deletion_parameter_input instanceof HTMLInputElement)) {
                return;
            }
            deletion_parameter_input.setAttribute(
                "name",
                getAttributeOrThrow(button, "data-deletion-parameter-name"),
            );
            deletion_parameter_input.setAttribute(
                "value",
                getAttributeOrThrow(button, "data-deletion-parameter-value"),
            );
        });
    });
}
