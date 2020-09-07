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

export function updateFloatingButtonsPosition(): void {
    const motd = document.querySelector(".motd");
    const header = document.querySelector("header");
    // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
    const message = document.querySelector("#current-project-banner-message") as HTMLElement;
    let ticking = false;

    if (!motd && header) {
        const scroll_event_listener = function (): void {
            if (!ticking && message) {
                window.requestAnimationFrame(() => {
                    const scroll_coordinates = {
                        x: window.pageXOffset,
                        y: window.pageYOffset,
                    };

                    if (scroll_coordinates.y > message.offsetHeight) {
                        resetButtonPosition(header);
                    } else {
                        setButtonPositionAccordingToMessage(message, header, scroll_coordinates.y);
                    }
                    ticking = false;
                });

                ticking = true;
            }
        };

        if (message && document.body.classList.contains("has-visible-project-banner")) {
            setButtonPositionAccordingToMessage(message, header, 0);
            window.addEventListener("scroll", scroll_event_listener);
        } else {
            resetButtonPosition(header);
            window.removeEventListener("scroll", scroll_event_listener);
        }
    }
}

function setButtonPositionAccordingToMessage(
    message: HTMLElement,
    header: HTMLElement,
    scroll_y: number
): void {
    if (message.classList.contains("project-banner-clamped")) {
        header.style.top = "60px";
    } else {
        header.style.top = message.offsetHeight - scroll_y + 36 + "px"; // magic value
    }
}

function resetButtonPosition(header: HTMLElement): void {
    header.style.top = "0px";
}
