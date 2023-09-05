/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

import { observePlatformBanner } from "../platform/banner/observe-platform-banner";
import { observeProjectBanner } from "../project/banner/observe-project-banner";
import { adjustHeaderPositionAccordingToBanners } from "./adjust-header-position-according-to-banners";

export function initHeaderPosition(): void {
    const header = document.querySelector("header");
    if (!(header instanceof HTMLElement)) {
        return;
    }

    const project_banner = document.querySelector(".project-banner");
    if (project_banner instanceof Element && !(project_banner instanceof HTMLElement)) {
        throw Error("Project banner exists but is not an HTMLElement");
    }
    const platform_banner = document.querySelector(".platform-banner");
    if (platform_banner instanceof Element && !(platform_banner instanceof HTMLElement)) {
        throw Error("Platform banner exists but is not an HTMLElement");
    }

    if (project_banner === null && platform_banner === null) {
        return;
    }

    adjustHeaderPositionAccordingToBanners(header, platform_banner, project_banner, window.scrollY);

    const mutation_observer = new MutationObserver(() => {
        adjustHeaderPositionAccordingToBanners(
            header,
            platform_banner,
            project_banner,
            window.scrollY,
        );
    });
    mutation_observer.observe(header, { attributes: true, attributeFilter: ["class"] });

    if (platform_banner) {
        observePlatformBanner(platform_banner, () => {
            adjustHeaderPositionAccordingToBanners(
                header,
                platform_banner,
                project_banner,
                window.scrollY,
            );
        });
    }

    if (project_banner) {
        observeProjectBanner(project_banner, () => {
            adjustHeaderPositionAccordingToBanners(
                header,
                platform_banner,
                project_banner,
                window.scrollY,
            );
        });
    }

    let ticking = false;
    let last_known_scroll_y = 0;
    window.addEventListener("scroll", () => {
        last_known_scroll_y = window.scrollY;
        if (!ticking) {
            window.requestAnimationFrame(() => {
                adjustHeaderPositionAccordingToBanners(
                    header,
                    platform_banner,
                    project_banner,
                    last_known_scroll_y,
                );
                ticking = false;
            });

            ticking = true;
        }
    });
}
