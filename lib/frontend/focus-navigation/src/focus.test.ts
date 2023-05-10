/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { describe, it, beforeEach, expect } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import { moveFocus } from "./focus";

describe(`focus`, () => {
    describe(`moveFocus()`, () => {
        let parent: HTMLElement,
            first: HTMLElement,
            second: HTMLElement,
            third: HTMLElement,
            fourth: HTMLElement,
            single_item: HTMLElement;

        const getParent = (): HTMLElement => parent;

        beforeEach(() => {
            document.body.innerHTML = `
                <div data-navigation="parent" data-test="parent">
                    <ul>First group
                        <li tabindex="0" data-navigation="item" data-test="first"></li>
                        <li tabindex="0" data-navigation="item" data-test="second"></li>
                    </ul>
                    <ul>Second group
                        <li tabindex="0" data-navigation="item" data-test="third"></li>
                        <li tabindex="0" data-navigation="item" data-test="fourth"></li>
                    </ul>
                    <span tabindex="0" data-navigation="single-item" data-test="single-item"></span>
                </div>
            `;
            parent = selectOrThrow(document.body, "[data-test=parent]");
            first = selectOrThrow(document.body, "[data-test=first]");
            second = selectOrThrow(document.body, "[data-test=second]");
            third = selectOrThrow(document.body, "[data-test=third]");
            fourth = selectOrThrow(document.body, "[data-test=fourth]");
            single_item = selectOrThrow(document.body, "[data-test=single-item]");
        });

        it(`when nothing is focused in the document, it does nothing`, () => {
            moveFocus(document, "down", getParent);
            expect(document.activeElement).toBe(document.body);
        });

        it(`when the focused element does not have a navigation marker, it does nothing`, () => {
            first.focus();
            delete first.dataset.navigation;
            moveFocus(document, "down", getParent);
            expect(document.activeElement).toBe(first);
        });

        it(`when the focused element is the only one that has a given data-navigation value,
            it does nothing`, () => {
            single_item.focus();
            moveFocus(document, "down", getParent);
            expect(document.activeElement).toBe(single_item);
        });

        it(`when getParent returns null, it does nothing`, () => {
            first.focus();
            moveFocus(document, "down", () => null);
            expect(document.activeElement).toBe(first);
        });

        describe(`when direction is "down"`, () => {
            const moveDown = (): void => moveFocus(document, "down", getParent);

            it(`focuses the next element with the same data-navigation value`, () => {
                first.focus();
                moveDown();
                expect(document.activeElement).toBe(second);
            });

            it(`and the focused element has no next sibling,
                it skips to the next group that is still descendant of the Element returned by getParent`, () => {
                second.focus();
                moveDown();
                expect(document.activeElement).toBe(third);
            });

            it(`and the focused element is the last element of its kind,
                it cycles back to the first element and focuses it`, () => {
                fourth.focus();
                moveDown();
                expect(document.activeElement).toBe(first);
            });
        });

        describe(`when direction is "up"`, () => {
            const moveUp = (): void => moveFocus(document, "up", getParent);

            it(`focuses the previous element with the same data-navigation value`, () => {
                second.focus();
                moveUp();
                expect(document.activeElement).toBe(first);
            });

            it(`and the focused element has no previous sibling,
                it skips to the previous group that is still descendant of the Element returned by getParent`, () => {
                third.focus();
                moveUp();
                expect(document.activeElement).toBe(second);
            });

            it(`and the focused element is the first element of its kind,
                it cycles back to the last element and focuses it`, () => {
                first.focus();
                moveUp();
                expect(document.activeElement).toBe(fourth);
            });
        });
    });
});
