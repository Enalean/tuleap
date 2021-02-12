/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

export {};

document.addEventListener("DOMContentLoaded", () => {
    const NAVBAR_HEIGHT_PX = 45;
    const LINE_HEIGHT_PX = 21;
    const NB_LINES_ABOVE_TARGET = 5;

    autoScrollToCurrent();
    window.addEventListener("hashchange", autoScrollToCurrent);

    function autoScrollToCurrent(): void {
        const element = getTargetElementForCurrentHash();
        if (!element) {
            return;
        }

        moveCodeLineHighlight(element);
        scrollToElementWithOffset(element);
    }

    function getTargetElementForCurrentHash(): null | HTMLElement {
        const hash = window.location.hash;
        if (!doesHashLooksLikeALineNumber(hash)) {
            return null;
        }

        const id = hash.slice(1);

        return document.getElementById(id);
    }

    function doesHashLooksLikeALineNumber(hash: string): boolean {
        return /^#L\d+$/.test(hash);
    }

    function scrollToElementWithOffset(element: HTMLElement): void {
        const { top } = element.getBoundingClientRect();
        const visible_top = top - (NAVBAR_HEIGHT_PX + NB_LINES_ABOVE_TARGET * LINE_HEIGHT_PX);
        const offset = window.pageYOffset + visible_top;

        window.scrollTo(window.pageXOffset, offset);
    }

    function moveCodeLineHighlight(element: HTMLElement): void {
        const parent_node = element.parentNode;
        if (!(parent_node instanceof HTMLElement)) {
            return;
        }

        const top = element.offsetTop - parent_node.offsetTop;
        const { height } = element.getBoundingClientRect();
        const line = document.getElementById("git-repository-highlight-line");
        if (!line) {
            return;
        }

        line.style.top = `${top}px`;
        line.style.height = `${height}px`;
        line.classList.add("shown");
    }
});
