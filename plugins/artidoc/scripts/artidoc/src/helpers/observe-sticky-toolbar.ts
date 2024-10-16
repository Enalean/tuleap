/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

export function observeStickyToolbar(
    toolbar: HTMLElement,
    onstuck: () => void,
    onunstuck: () => void,
): void {
    const header = document.querySelector("header");
    if (!header) {
        return;
    }

    const detectIfToolbarIsStuck = (): void => {
        const header_bottom = header.getBoundingClientRect().bottom;
        const toolbar_top = toolbar.getBoundingClientRect().top;
        if (toolbar_top <= header_bottom) {
            onstuck();
        } else {
            onunstuck();
        }
    };

    let ticking = false;
    window.addEventListener("scroll", () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                detectIfToolbarIsStuck();
                ticking = false;
            });
        }
        ticking = true;
    });
}
