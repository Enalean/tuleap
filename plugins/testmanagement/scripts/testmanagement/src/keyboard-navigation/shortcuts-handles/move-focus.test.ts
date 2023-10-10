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

import { getTargetTest, moveFocus } from "./move-focus";
import { BOTTOM, NEXT, PREVIOUS, TOP } from "../type";

import * as getter_current_test from "./get-current-test";
import * as getter_current_category from "./get-current-category";

jest.mock("./get-current-test");
jest.mock("./get-current-category");

describe("move-focus", () => {
    let doc: Document;

    let first_category: HTMLElement;
    let second_category: HTMLElement;

    let first_test: HTMLLIElement;
    let second_test: HTMLLIElement;
    let third_test: HTMLLIElement;
    let fourth_test: HTMLLIElement;

    let first_test_link: HTMLAnchorElement;
    let second_test_link: HTMLAnchorElement;
    let third_test_link: HTMLAnchorElement;
    let fourth_test_link: HTMLAnchorElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupTestsListDocument(doc);
    });

    describe("moveFocus", () => {
        it("focuses the first test tab if getTargetTest() returns an element", () => {
            const focus = jest.spyOn(first_test_link, "focus");
            jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(second_test);

            moveFocus(doc, TOP);

            expect(focus).toHaveBeenCalled();
        });
    });

    describe("getTargetTest", () => {
        it("returns null if no test is focused", () => {
            jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(null);
            const target_test = getTargetTest(doc, TOP);

            expect(target_test).toBeNull();
        });

        it("throws an error if test link does not have the [data-navigation-test-link] attribute", () => {
            jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(first_test);
            second_test_link.removeAttribute("data-navigation-test-link");

            expect(() => {
                getTargetTest(doc, NEXT);
            }).toThrow();
        });

        it("returns the first test link in document", () => {
            jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(second_test);
            const target_test = getTargetTest(doc, TOP);

            expect(target_test).toBe(first_test_link);
        });

        it("return the last test link in document", () => {
            jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(second_test);
            const target_test = getTargetTest(doc, BOTTOM);

            expect(target_test).toBe(fourth_test_link);
        });

        describe("NEXT", () => {
            it("returns the next test link", () => {
                jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(first_test);
                const target_test = getTargetTest(doc, NEXT);

                expect(target_test).toBe(second_test_link);
            });

            it("returns the first test link in next category if current test is last of its category", () => {
                jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(second_test);
                jest.spyOn(getter_current_category, "getCurrentCategory").mockReturnValue(
                    first_category,
                );
                const target_test = getTargetTest(doc, NEXT);

                expect(target_test).toBe(third_test_link);
            });

            it("returns first test link if current test is the last", () => {
                jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(fourth_test);
                jest.spyOn(getter_current_category, "getCurrentCategory").mockReturnValue(
                    second_category,
                );
                const target_test = getTargetTest(doc, NEXT);

                expect(target_test).toBe(first_test_link);
            });
        });

        describe("PREVIOUS", () => {
            it("returns the previous test link", () => {
                jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(second_test);
                const target_test = getTargetTest(doc, PREVIOUS);

                expect(target_test).toBe(first_test_link);
            });

            it("returns the last test link in previous category if current test is first of its category", () => {
                jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(third_test);
                jest.spyOn(getter_current_category, "getCurrentCategory").mockReturnValue(
                    second_category,
                );
                const target_test = getTargetTest(doc, PREVIOUS);

                expect(target_test).toBe(second_test_link);
            });

            it("returns last test link if current test is the first", () => {
                jest.spyOn(getter_current_test, "getCurrentTest").mockReturnValue(first_test);
                jest.spyOn(getter_current_category, "getCurrentCategory").mockReturnValue(
                    first_category,
                );
                const target_test = getTargetTest(doc, PREVIOUS);

                expect(target_test).toBe(fourth_test_link);
            });
        });
    });

    function setupTestsListDocument(doc: Document): void {
        first_category = doc.createElement("div");
        first_category.setAttribute("data-navigation-category", "");
        second_category = doc.createElement("div");
        second_category.setAttribute("data-navigation-category", "");

        first_test = doc.createElement("li");
        second_test = doc.createElement("li");
        third_test = doc.createElement("li");
        fourth_test = doc.createElement("li");

        [first_test, second_test, third_test, fourth_test].forEach((test) => {
            test.setAttribute("data-navigation-test", "");
        });

        first_test_link = doc.createElement("a");
        second_test_link = doc.createElement("a");
        third_test_link = doc.createElement("a");
        fourth_test_link = doc.createElement("a");

        [first_test_link, second_test_link, third_test_link, fourth_test_link].forEach(
            (test_link) => {
                test_link.setAttribute("data-navigation-test-link", "");
            },
        );

        first_test.append(first_test_link);
        second_test.append(second_test_link);
        third_test.append(third_test_link);
        fourth_test.append(fourth_test_link);

        first_category.append(first_test, second_test);
        second_category.append(third_test, fourth_test);

        doc.body.append(first_category, second_category);
    }
});
