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

import { moveFocus, getTargetTestTab } from "./move-focus";
import { Direction } from "./type";

describe("move-focus", () => {
    let doc: Document;

    let first_test: HTMLAnchorElement;
    let second_test: HTMLAnchorElement;
    let third_test: HTMLAnchorElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupTestsListDocument(doc);
    });

    describe("moveFocus()", () => {
        let focus: jest.SpyInstance;
        let current_test_tab: EventTarget;

        beforeEach(() => {
            focus = jest.spyOn(first_test, "focus");
        });

        it("returns nothing if current test tab is not an anchor HTML element with the 'data-shortcut-navigation' attribute", () => {
            current_test_tab = doc.createElement("span");
            moveFocus(doc, current_test_tab, Direction.top);

            expect(focus).not.toHaveBeenCalled();
        });

        it("focuses the first test tab", () => {
            current_test_tab = doc.createElement("a");
            if (current_test_tab instanceof HTMLAnchorElement) {
                current_test_tab.setAttribute("data-shortcut-navigation", "");
            }

            moveFocus(doc, current_test_tab, Direction.top);

            expect(focus).toHaveBeenCalled();
        });
    });

    describe("getTargetTestTab()", () => {
        it("returns the last test tab in the given tests tablist", () => {
            const target_test_tab = getTargetTestTab(doc, second_test, Direction.bottom);
            expect(target_test_tab).toBe(third_test);
        });

        it("returns the first test tab in the given tests tablist", () => {
            const target_test_tab = getTargetTestTab(doc, second_test, Direction.top);
            expect(target_test_tab).toBe(first_test);
        });

        it("returns the previous test tab in the given tests tablist", () => {
            const target_test_tab = getTargetTestTab(doc, second_test, Direction.previous);
            expect(target_test_tab).toBe(first_test);
        });

        it("returns the next test tab in the given tests tablist", () => {
            const target_test_tab = getTargetTestTab(doc, second_test, Direction.next);
            expect(target_test_tab).toBe(third_test);
        });

        it("returns the first test tab when current test tab is the last in the tests tablist", () => {
            const target_test_tab = getTargetTestTab(doc, third_test, Direction.next);
            expect(target_test_tab).toBe(first_test);
        });

        it("returns the last test tab when current test tab is the first in the tests tablist", () => {
            const target_test_tab = getTargetTestTab(doc, first_test, Direction.previous);
            expect(target_test_tab).toBe(third_test);
        });

        it("returns null if last test tab is not an anchor HTML element", () => {
            const fourth_test = doc.createElement("span");
            fourth_test.setAttribute("data-shortcut-navigation", "");
            doc.body.append(fourth_test);

            const target_test_tab = getTargetTestTab(doc, third_test, Direction.bottom);

            expect(target_test_tab).toBe(null);
        });

        it("returns null if current tab position in list could not be found", () => {
            second_test.removeAttribute("data-test-tab-index");
            const target_test_tab = getTargetTestTab(doc, second_test, Direction.next);

            expect(target_test_tab).toBe(null);
        });
    });

    function setupTestsListDocument(doc: Document): void {
        const tests_list = doc.createElement("div");
        first_test = doc.createElement("a");
        second_test = doc.createElement("a");
        third_test = doc.createElement("a");

        [first_test, second_test, third_test].forEach((test, index) => {
            test.setAttribute("data-test-tab-index", index.toString());
            test.setAttribute("data-shortcut-navigation", "");
        });

        tests_list.append(first_test, second_test, third_test);
        doc.body.append(tests_list);
    }
});
