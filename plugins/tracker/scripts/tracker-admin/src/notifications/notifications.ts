/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

import { createModal, openAllTargetModalsOnClick } from "@tuleap/tlp-modal";
import { initUsersAutocompleter } from "@tuleap/lazybox-users-autocomplete";
import { createLazybox } from "@tuleap/lazybox";
import { getAttributeOrThrow, selectOrThrow } from "@tuleap/dom";
import type { User } from "@tuleap/core-rest-api-types";
import { getLocaleWithDefault } from "@tuleap/gettext";
import type { LocaleString } from "@tuleap/locale";

document.addEventListener("DOMContentLoaded", function () {
    initModalGlobalNotification();
    initAllNotificationModal();

    initModalUnsubscribeNotificationAdd();

    const locale = getLocaleWithDefault(document);
    initModalGlobalNotificationAutocomplete(locale);

    initModalDateReminderNotificationAdd();
});

function initModalDateReminderNotificationAdd(): void {
    const modal_element = selectOrThrow(document, "#date-field-reminder-form");
    const modal = createModal(modal_element);
    const add_button_global_notif = selectOrThrow(document, "#add-reminder-button");
    add_button_global_notif.addEventListener("click", () => {
        modal.show();
    });
}

function initModalUnsubscribeNotificationAdd(): void {
    const modal_element = selectOrThrow(document, "#tracker-unsubscribe-add-modal");
    const modal = createModal(modal_element);
    const add_button_unsubscribe = selectOrThrow(document, "#tracker-unsubscribe-add-button");
    add_button_unsubscribe.addEventListener("click", () => {
        modal.show();
    });
}

function initAllNotificationModal(): void {
    openAllTargetModalsOnClick(document, ".tracker-notification-modal-button-trigger");
}

function initModalGlobalNotification(): void {
    const modal_element = selectOrThrow(document, "#tracker-global-notifications-add-modal");
    const modal = createModal(modal_element);
    const add_button_global_notif = selectOrThrow(document, "#tracker-global-notifications-add");
    add_button_global_notif.addEventListener("click", () => {
        modal.show();
    });
}

function initModalGlobalNotificationAutocomplete(locale: LocaleString): void {
    document.querySelectorAll(".tracker-global-notifications-autocomplete").forEach((container) => {
        if (!(container instanceof HTMLElement)) {
            return;
        }

        const lazybox = createLazybox(document);
        lazybox.id = getAttributeOrThrow(container, "data-id");
        container.appendChild(lazybox);

        const existing = JSON.parse(getAttributeOrThrow(container, "data-existing"));

        const hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = getAttributeOrThrow(container, "data-name");
        container.appendChild(hidden);

        initUsersAutocompleter(
            lazybox,
            existing,
            (selected_users: ReadonlyArray<User>): void => {
                hidden.value = selected_users.map((user: User): number => user.id).join(",");
            },
            locale,
        );
    });
}
