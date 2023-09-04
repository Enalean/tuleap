/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

export const PLATFORM_BANNER_NAVBAR_ID = "platform-banner-bullhorn";
export const PLATFORM_BANNER_MESSAGE_CLOSE_BUTTON_ID = "platform-banner-close";
export const PLATFORM_BANNER_VISIBLE_GLOBAL_CLASS = "has-visible-platform-banner";
export const PLATFORM_BANNER_HIDDEN_CLASS = "platform-banner-hidden";

export function allowToHideAndShowPlatformBanner(
    mount_point: Document,
    tlpPatch: (url: string, init: RequestInit & { method?: "PATCH" }) => Promise<Response>,
): void {
    const platform_banner_navbar = mount_point.getElementById(PLATFORM_BANNER_NAVBAR_ID);
    const platform_banner_message_close_button = mount_point.getElementById(
        PLATFORM_BANNER_MESSAGE_CLOSE_BUTTON_ID,
    );

    if (platform_banner_navbar === null || platform_banner_message_close_button === null) {
        return;
    }

    const full_platform_banner = platform_banner_message_close_button.parentElement;
    if (full_platform_banner === null) {
        throw new Error(
            "Platform banner close button is supposed to be contained in the platform banner",
        );
    }

    platform_banner_navbar.addEventListener("click", (event: Event): void => {
        togglePlatformBannerMessage(
            event,
            mount_point.body,
            platform_banner_navbar,
            full_platform_banner,
        );
    });
    const user_id = platform_banner_message_close_button.dataset.userId;
    if (user_id === undefined) {
        throw new Error(
            "Platform banner close button is supposed to have information about the current user",
        );
    }
    platform_banner_message_close_button.addEventListener("click", async (): Promise<void> => {
        await hidePlatformBannerMessage(
            tlpPatch,
            mount_point.body,
            platform_banner_navbar,
            full_platform_banner,
            Number.parseInt(user_id, 10),
        );
    });
}

function togglePlatformBannerMessage(
    event: Event,
    document_body: HTMLElement,
    platform_banner_navbar: HTMLElement,
    full_platform_banner: HTMLElement,
): void {
    event.preventDefault();
    window.scrollTo(0, 0);
    document_body.classList.add(PLATFORM_BANNER_VISIBLE_GLOBAL_CLASS);
    platform_banner_navbar.classList.add(PLATFORM_BANNER_HIDDEN_CLASS);
    full_platform_banner.classList.remove(PLATFORM_BANNER_HIDDEN_CLASS);
}

async function hidePlatformBannerMessage(
    tlpPatch: (url: string, init: RequestInit & { method?: "PATCH" }) => Promise<Response>,
    document_body: HTMLElement,
    platform_banner_navbar: HTMLElement,
    full_platform_banner: HTMLElement,
    user_id: number,
): Promise<void> {
    document_body.classList.remove(PLATFORM_BANNER_VISIBLE_GLOBAL_CLASS);
    platform_banner_navbar.classList.remove(PLATFORM_BANNER_HIDDEN_CLASS);
    full_platform_banner.classList.add(PLATFORM_BANNER_HIDDEN_CLASS);

    // Not dealing with potential errors here, worst case scenario the user will have to close the banner again on the next page
    await tlpPatch(`/api/users/${encodeURIComponent(user_id)}/preferences`, {
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ key: `platform_banner`, value: "hidden" }),
    });
}
