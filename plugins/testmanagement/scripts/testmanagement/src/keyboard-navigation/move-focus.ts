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

import { Direction } from "./type";

export function moveFocus(
    doc: Document,
    current_test_tab: EventTarget | null,
    direction: Direction
): void {
    if (
        !(current_test_tab instanceof HTMLAnchorElement) ||
        !current_test_tab.hasAttribute("data-shortcut-navigation")
    ) {
        return;
    }
    const target_test_tab = getTargetTestTab(doc, current_test_tab, direction);
    if (target_test_tab) {
        target_test_tab.focus();
    }
}

export function getTargetTestTab(
    doc: Document,
    current_test_tab: HTMLAnchorElement,
    direction: Direction
): HTMLAnchorElement | null {
    const tests_tablist = doc.querySelectorAll("[data-shortcut-navigation]");

    if (direction === Direction.bottom) {
        return getLastTestTab(tests_tablist);
    }

    if (direction === Direction.top) {
        return getFirstTestTab(tests_tablist);
    }

    const current_test_tab_position = getCurrentTabPositionInList(tests_tablist, current_test_tab);
    if (current_test_tab_position === null) {
        return null;
    }

    const next_tab = getNextTestTab(tests_tablist, current_test_tab_position);
    if (direction === Direction.next) {
        return next_tab ? next_tab : getFirstTestTab(tests_tablist);
    }

    const previous_tab = getPreviousTestTab(tests_tablist, current_test_tab_position);
    if (direction === Direction.previous) {
        return previous_tab ? previous_tab : getLastTestTab(tests_tablist);
    }

    return null;
}

function getCurrentTabPositionInList(
    tests_tablist: NodeList,
    current_test_tab: HTMLAnchorElement
): number | null {
    const tab_position_index = current_test_tab.getAttribute("data-test-tab-index");
    if (!tab_position_index) {
        return null;
    }
    return parseInt(tab_position_index, 10);
}

function getPreviousTestTab(
    tests_tablist: NodeList,
    current_test_tab_position: number
): HTMLAnchorElement | null {
    const previous_tab = tests_tablist[current_test_tab_position - 1];
    if (!(previous_tab instanceof HTMLAnchorElement)) {
        return null;
    }
    return previous_tab;
}

function getNextTestTab(
    tests_tablist: NodeList,
    current_test_tab_position: number
): HTMLAnchorElement | null {
    const next_tab = tests_tablist[current_test_tab_position + 1];
    if (!(next_tab instanceof HTMLAnchorElement)) {
        return null;
    }
    return next_tab;
}

function getFirstTestTab(tests_tablist: NodeList): HTMLAnchorElement | null {
    const first_tab = tests_tablist[0];
    if (!(first_tab instanceof HTMLAnchorElement)) {
        return null;
    }
    return first_tab;
}

function getLastTestTab(tests_tablist: NodeList): HTMLAnchorElement | null {
    const last_tab = tests_tablist[tests_tablist.length - 1];
    if (!(last_tab instanceof HTMLAnchorElement)) {
        return null;
    }
    return last_tab;
}
