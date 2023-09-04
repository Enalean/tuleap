/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

export function adjustHeaderPositionAccordingToBanners(
    header: HTMLElement,
    platform_banner: HTMLElement | null,
    project_banner: HTMLElement | null,
    scroll_y: number,
): void {
    if (project_banner === null && platform_banner === null) {
        return;
    }

    if (project_banner === null || project_banner.classList.contains("project-banner-hidden")) {
        if (platform_banner === null) {
            return;
        }

        if (platform_banner.classList.contains("platform-banner-hidden")) {
            header.style.top = "0px";
        } else {
            header.style.top = platform_banner.offsetHeight + "px";
        }
        return;
    }

    if (platform_banner === null || platform_banner.classList.contains("platform-banner-hidden")) {
        if (header.classList.contains("pinned")) {
            header.style.top = "0px";
            return;
        }

        header.style.top = Math.max(project_banner.offsetHeight - scroll_y, 0) + "px";
        return;
    }

    if (header.classList.contains("pinned")) {
        header.style.top = platform_banner.offsetHeight + "px";
        return;
    }

    header.style.top = platform_banner.offsetHeight - header.offsetHeight - 8 + "px";
}
