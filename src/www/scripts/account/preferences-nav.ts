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

document.addEventListener("DOMContentLoaded", () => {
    let ticking = false;
    // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
    const nav = document.querySelector(".user-preferences-nav") as Element;

    flipPreferencesNav();

    window.addEventListener("resize", () => {
        if (!ticking) {
            requestAnimationFrame(() => {
                flipPreferencesNav();
                ticking = false;
            });

            ticking = true;
        }
    });

    function flipPreferencesNav(): void {
        if (window.innerWidth < 1000) {
            nav.classList.remove("tlp-tabs-vertical");
        } else {
            nav.classList.add("tlp-tabs-vertical");
        }
    }
});
