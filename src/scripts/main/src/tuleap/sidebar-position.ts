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

export function initSidebarPosition(): void {
    const platform_banner = document.querySelector(".platform-banner");
    if (!(platform_banner instanceof HTMLElement)) {
        return;
    }

    setPlatformBannerOffsetHeightCSSVar(platform_banner);
    observePlatformBanner(platform_banner, () => {
        setPlatformBannerOffsetHeightCSSVar(platform_banner);
    });
}

function setPlatformBannerOffsetHeightCSSVar(platform_banner: HTMLElement): void {
    document.documentElement.style.setProperty(
        "--platform-banner-offset-height",
        platform_banner.offsetHeight + "px",
    );
}
