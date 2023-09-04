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

import { DOWN, UP, RIGHT, LEFT } from "../type";
import { moveFocus } from "./move-focus";

describe("move-focus", () => {
    let first_swimlane: HTMLElement;
    let second_swimlane: HTMLElement;
    let last_swimlane: HTMLElement;

    let first_card: HTMLElement;
    let second_card: HTMLElement;
    let add_form: HTMLElement;

    let first_cell_card: HTMLElement;
    let second_cell_card: HTMLElement;
    let last_cell_add_form: HTMLElement;

    beforeEach(() => {
        document.body.innerHTML = "";
        setupDocument(document);
    });

    it("does nothing if no element with a data-navigation attribute has focus", () => {
        moveFocus(document, DOWN);

        expect(document.activeElement).toBe(document.body);
    });

    describe("current element is a swimlane", () => {
        beforeEach(() => {
            const maybe_first_swimlane = document.querySelector("[data-test='first-swimlane']");
            const maybe_second_swimlane = document.querySelector("[data-test='second-swimlane']");
            const maybe_last_swimlane = document.querySelector("[data-test='last-swimlane']");

            if (
                !(maybe_first_swimlane instanceof HTMLElement) ||
                !(maybe_second_swimlane instanceof HTMLElement) ||
                !(maybe_last_swimlane instanceof HTMLElement)
            ) {
                throw new Error("Bad setup");
            }

            first_swimlane = maybe_first_swimlane;
            second_swimlane = maybe_second_swimlane;
            last_swimlane = maybe_last_swimlane;
        });

        describe("moving DOWN", () => {
            it("focuses the second swimlane if first swimlane is focused", () => {
                first_swimlane.focus();
                moveFocus(document, DOWN);

                expect(document.activeElement).toBe(second_swimlane);
            });

            it("focuses the first swimlane if last swimlane is focused", () => {
                last_swimlane.focus();
                moveFocus(document, DOWN);

                expect(document.activeElement).toBe(first_swimlane);
            });
        });

        describe("moving UP", () => {
            it("focuses the first swimlane if second swimlane is focused", () => {
                second_swimlane.focus();
                moveFocus(document, UP);

                expect(document.activeElement).toBe(first_swimlane);
            });

            it("focuses the last swimlane if first swimlane is focused", () => {
                first_swimlane.focus();
                moveFocus(document, UP);

                expect(document.activeElement).toBe(last_swimlane);
            });
        });
    });

    describe("current element is a card or add form", () => {
        describe("moving UP and DOWN", () => {
            beforeEach(() => {
                const maybe_first_card = document.querySelector(
                    "[data-test='first-cell-first-card']",
                );
                const maybe_second_card = document.querySelector(
                    "[data-test='first-cell-second-card']",
                );
                const maybe_add_form = document.querySelector("[data-test='first-cell-add-form']");

                if (
                    !(maybe_first_card instanceof HTMLElement) ||
                    !(maybe_second_card instanceof HTMLElement) ||
                    !(maybe_add_form instanceof HTMLElement)
                ) {
                    throw new Error("Bad setup");
                }

                first_card = maybe_first_card;
                second_card = maybe_second_card;
                add_form = maybe_add_form;
            });

            describe("moving DOWN", () => {
                it("focuses the second card if first card is focused", () => {
                    first_card.focus();
                    moveFocus(document, DOWN);

                    expect(document.activeElement).toBe(second_card);
                });

                it("focuses the add form if second and last card is focused", () => {
                    second_card.focus();
                    moveFocus(document, DOWN);

                    expect(document.activeElement).toBe(add_form);
                });

                it("focuses the first card if add form is focused", () => {
                    add_form.focus();
                    moveFocus(document, DOWN);

                    expect(document.activeElement).toBe(first_card);
                });
            });

            describe("moving UP", () => {
                it("focuses the first card if second card is focused", () => {
                    second_card.focus();
                    moveFocus(document, UP);

                    expect(document.activeElement).toBe(first_card);
                });

                it("focuses the add form if first card is focused", () => {
                    first_card.focus();
                    moveFocus(document, UP);

                    expect(document.activeElement).toBe(add_form);
                });

                it("focuses the second and last card if add form is focused", () => {
                    add_form.focus();
                    moveFocus(document, UP);

                    expect(document.activeElement).toBe(second_card);
                });
            });
        });

        describe("moving RIGHT and LEFT", () => {
            beforeEach(() => {
                const maybe_first_cell_card = document.querySelector(
                    "[data-test='first-cell-first-card']",
                );
                const maybe_second_cell_card = document.querySelector(
                    "[data-test='second-cell-first-card']",
                );
                const maybe_last_cell_add_form = document.querySelector(
                    "[data-test='last-cell-add-form']",
                );

                if (
                    !(maybe_first_cell_card instanceof HTMLElement) ||
                    !(maybe_second_cell_card instanceof HTMLElement) ||
                    !(maybe_last_cell_add_form instanceof HTMLElement)
                ) {
                    throw new Error("Bad setup");
                }

                first_cell_card = maybe_first_cell_card;
                second_cell_card = maybe_second_cell_card;
                last_cell_add_form = maybe_last_cell_add_form;
            });

            describe("moving RIGHT", () => {
                it("focuses the first card in second cell if focus is in first cell", () => {
                    first_cell_card.focus();
                    moveFocus(document, RIGHT);

                    expect(document.activeElement).toBe(second_cell_card);
                });

                it("focuses the add form in right cell if it contains no cards", () => {
                    second_cell_card.focus();
                    moveFocus(document, RIGHT);

                    expect(document.activeElement).toBe(last_cell_add_form);
                });

                it("focuses the first card in first cell if focus is in last cell", () => {
                    last_cell_add_form.focus();
                    moveFocus(document, RIGHT);

                    expect(document.activeElement).toBe(first_cell_card);
                });
            });

            describe("moving LEFT", () => {
                it("focuses the first card in first cell if focus is in second cell", () => {
                    second_cell_card.focus();
                    moveFocus(document, LEFT);

                    expect(document.activeElement).toBe(first_cell_card);
                });

                it("focuses the add form in last cell if it contains no cards and focus was in first cell", () => {
                    first_cell_card.focus();
                    moveFocus(document, LEFT);

                    expect(document.activeElement).toBe(last_cell_add_form);
                });
            });
        });
    });

    function setupDocument(doc: Document): void {
        doc.body.insertAdjacentHTML(
            "beforeend",
            `
                <div tabindex="0" data-navigation="swimlane" data-test="first-swimlane">
                    <div data-navigation="cell">
                        <div tabindex="0" data-navigation="card" data-test="first-cell-first-card"></div>
                        <div tabindex="0" data-navigation="card" data-test="first-cell-second-card"></div>
                        <div tabindex="0" data-navigation="add-form" data-test="first-cell-add-form"></div>
                    </div>
                    <div data-navigation="cell">
                        <div tabindex="0" data-navigation="card" data-test="second-cell-first-card"></div>
                        <div tabindex="0" data-navigation="add-form" data-test="second-cell-add-form"></div>
                    </div>
                    <div data-navigation="cell">
                        <div tabindex="0" data-navigation="add-form" data-test="last-cell-add-form"></div>
                    </div>
                </div>
                <div tabindex="0" data-navigation="swimlane" data-test="second-swimlane"></div>
                <div tabindex="0" data-navigation="swimlane" data-test="last-swimlane"></div>
            `,
        );
    }
});
