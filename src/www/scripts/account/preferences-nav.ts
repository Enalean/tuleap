/**
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
 *
 */

const nav = document.querySelector(".user-preferences-nav");
if (nav === null) {
    throw Error("Navigation element for preferences is not found");
}

const tlp_spacing = 20;
const tlp_spacing_condensed = 15;

const width =
    1000 +
    2 * (document.body.classList.contains("theme-condensed") ? tlp_spacing_condensed : tlp_spacing);

flipPreferencesNav(nav);

let ticking = false;
window.addEventListener("resize", () => {
    if (!ticking) {
        requestAnimationFrame(() => {
            flipPreferencesNav(nav);
            ticking = false;
        });

        ticking = true;
    }
});

function flipPreferencesNav(nav: Element): void {
    if (window.innerWidth <= width) {
        nav.classList.remove("tlp-tabs-vertical");
    } else {
        nav.classList.add("tlp-tabs-vertical");
    }
}
