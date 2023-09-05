/**
 * Copyright (c) 2020-present, Enalean. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

export async function actionsOnHelpMenuOpened(
    help_button: HTMLElement,
    tlp_post: (url: string, init: RequestInit & { method?: "POST" }) => Promise<Response>,
): Promise<void> {
    helpButtonMarkAsRead(help_button);

    await tlp_post("/help_menu_opened", {});
}

function helpButtonMarkAsRead(help_button: HTMLElement): void {
    if (help_button.classList.contains("new-release-note-available")) {
        help_button.classList.remove("new-release-note-available");
    }
}
