/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

export const PLATFORM_BANNER_MESSAGE_ID = "platform-banner-message";
export const PLATFORM_BANNER_MESSAGE_CLAMP_CLASS = "platform-banner-clamped";
export const PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS = "platform-banner-can-be-unclamped";

export function allowUnclampingPlatformBannerMessage(mount_point: Document): void {
    const platform_banner_message = mount_point.getElementById(PLATFORM_BANNER_MESSAGE_ID);
    if (
        platform_banner_message === null ||
        platform_banner_message.parentElement === null ||
        platform_banner_message.parentElement.parentElement === null
    ) {
        return;
    }
    const platform_banner_message_wrapper = platform_banner_message.parentElement.parentElement;

    addHintMessageIsClampedIfNeeded(platform_banner_message);

    const observer = new MutationObserver(() => {
        addHintMessageIsClampedIfNeeded(platform_banner_message);
    });
    observer.observe(platform_banner_message_wrapper, { attributes: true });

    let ticking = false;
    const resize_event_listener = function (): void {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                addHintMessageIsClampedIfNeeded(platform_banner_message);
                ticking = false;
            });
            ticking = true;
        }
    };
    const fully_display_message_event_listener = function (): void {
        fullyDisplayPlatformBannerMessage(
            platform_banner_message,
            fully_display_message_event_listener,
        );
    };
    window.addEventListener("resize", resize_event_listener);
    platform_banner_message.addEventListener("click", fully_display_message_event_listener);
}

function isMessageClamped(banner_message: HTMLElement): boolean {
    return banner_message.scrollWidth > banner_message.clientWidth;
}

function fullyDisplayPlatformBannerMessage(
    platform_banner_message: HTMLElement,
    fully_display_event_listener: () => void,
): void {
    platform_banner_message.classList.remove(
        PLATFORM_BANNER_MESSAGE_CLAMP_CLASS,
        PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS,
    );

    platform_banner_message.removeEventListener("click", fully_display_event_listener);
}

function addHintMessageIsClampedIfNeeded(platform_banner_message: HTMLElement): void {
    if (isMessageClamped(platform_banner_message)) {
        platform_banner_message.classList.add(PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS);
    } else {
        platform_banner_message.classList.remove(PLATFORM_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS);
    }
}
