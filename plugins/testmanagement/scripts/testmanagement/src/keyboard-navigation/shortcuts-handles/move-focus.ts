/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { Direction } from "../type";
import { BOTTOM, NEXT, PREVIOUS, TOP } from "../type";

import { getCurrentTest } from "./get-current-test";
import { getCurrentCategory } from "./get-current-category";

export function moveFocus(doc: Document, direction: Direction): void {
    const target_test_tab = getTargetTest(doc, direction);
    if (target_test_tab) {
        target_test_tab.focus();
    }
}

export function getTargetTest(doc: Document, direction: Direction): HTMLAnchorElement | null {
    const current_test = getCurrentTest(doc);
    if (!current_test) {
        return null;
    }

    if (direction === TOP) {
        return getFirstTestLink(doc);
    }

    if (direction === BOTTOM) {
        return getLastTestLink(doc);
    }

    const current_category = getCurrentCategory(doc);

    if (direction === NEXT) {
        return getNextTestLink(doc, current_test, current_category);
    }

    if (direction === PREVIOUS) {
        return getPreviousTestLink(doc, current_test, current_category);
    }

    throw new Error("Incorrect Direction member");
}

function getFirstTestLink(link_container: HTMLElement | Document): HTMLAnchorElement {
    const first_link = link_container.querySelector("[data-navigation-test-link]");
    if (!(first_link instanceof HTMLAnchorElement)) {
        throw new Error(
            `Could not find an anchor element with [data-navigation-test-link] attribute in ${link_container}`,
        );
    }
    return first_link;
}

function getLastTestLink(link_container: HTMLElement | Document): HTMLAnchorElement {
    const links = link_container.querySelectorAll("[data-navigation-test-link]");
    const last_link = links[links.length - 1];
    if (!(last_link instanceof HTMLAnchorElement)) {
        throw new Error(
            `Could not find last anchor element with [data-navigation-test-link] attribute in ${link_container}`,
        );
    }
    return last_link;
}

function getNextTestLink(
    doc: Document,
    current_test: HTMLElement,
    current_category: HTMLElement,
): HTMLAnchorElement {
    const next_test_tab = current_test.nextElementSibling;
    if (next_test_tab instanceof HTMLElement) {
        return getFirstTestLink(next_test_tab);
    }

    const next_category = current_category.nextElementSibling;
    return next_category instanceof HTMLElement
        ? getFirstTestLink(next_category)
        : getFirstTestLink(doc);
}

function getPreviousTestLink(
    doc: Document,
    current_test: HTMLElement,
    current_category: HTMLElement,
): HTMLAnchorElement {
    const previous_test_tab = current_test.previousElementSibling;
    if (previous_test_tab instanceof HTMLElement) {
        return getFirstTestLink(previous_test_tab);
    }

    const previous_category = current_category.previousElementSibling;
    return previous_category instanceof HTMLElement
        ? getLastTestLink(previous_category)
        : getLastTestLink(doc);
}
