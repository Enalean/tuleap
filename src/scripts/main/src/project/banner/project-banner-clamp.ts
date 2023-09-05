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

export const PROJECT_BANNER_MESSAGE_ID = "current-project-banner-message";
export const PROJECT_BANNER_MESSAGE_CLAMP_CLASS = "project-banner-clamped";
export const PROJECT_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS = "project-banner-can-be-unclamped";

export function allowUnclampingProjectBannerMessage(mount_point: Document): void {
    const project_banner_message = mount_point.getElementById(PROJECT_BANNER_MESSAGE_ID);
    if (project_banner_message === null || project_banner_message.parentElement === null) {
        return;
    }
    const project_banner_message_wrapper = project_banner_message.parentElement;

    addHintMessageIsClampedIfNeeded(project_banner_message);

    const observer = new MutationObserver(() => {
        addHintMessageIsClampedIfNeeded(project_banner_message);
    });
    observer.observe(project_banner_message_wrapper, { attributes: true });

    let ticking = false;
    const resize_event_listener = function (): void {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                addHintMessageIsClampedIfNeeded(project_banner_message);
                ticking = false;
            });
            ticking = true;
        }
    };
    const fully_display_message_event_listener = function (): void {
        fullyDisplayProjectBannerMessage(
            project_banner_message,
            fully_display_message_event_listener,
        );
    };
    window.addEventListener("resize", resize_event_listener);
    project_banner_message.addEventListener("click", fully_display_message_event_listener);
}

function isMessageClamped(banner_message: HTMLElement): boolean {
    return banner_message.scrollWidth > banner_message.clientWidth;
}

function fullyDisplayProjectBannerMessage(
    project_banner_message: HTMLElement,
    fully_display_event_listener: () => void,
): void {
    project_banner_message.classList.remove(
        PROJECT_BANNER_MESSAGE_CLAMP_CLASS,
        PROJECT_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS,
    );

    project_banner_message.removeEventListener("click", fully_display_event_listener);
}

function addHintMessageIsClampedIfNeeded(project_banner_message: HTMLElement): void {
    if (isMessageClamped(project_banner_message)) {
        project_banner_message.classList.add(PROJECT_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS);
    } else {
        project_banner_message.classList.remove(PROJECT_BANNER_MESSAGE_CAN_BE_UNCLAMPED_CLASS);
    }
}
