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

import {
    getElementDown,
    getElementLeft,
    getElementRight,
    getElementUp,
} from "./get-element-from-direction";

describe("get-element-from-direction", () => {
    let first_swimlane: HTMLElement;
    let second_swimlane: HTMLElement;

    let first_cell: HTMLElement;
    let second_cell: HTMLElement;
    let third_cell: HTMLElement;

    let first_card: HTMLElement;
    let second_card: HTMLElement;
    let third_card: HTMLElement;

    let first_add_button: HTMLButtonElement;
    let second_add_button: HTMLButtonElement;

    beforeEach(() => {
        document.body.innerHTML = "";
        setupDocument(document);
    });

    describe("getElementDown", () => {
        describe("current element is a swimlane", () => {
            it("returns the next swimlane", () => {
                const next_element = getElementDown(document, first_swimlane);
                expect(next_element).toBe(second_swimlane);
            });

            it("returns the first swimlane if current one is the last swimlane", () => {
                const next_element = getElementDown(document, second_swimlane);
                expect(next_element).toBe(first_swimlane);
            });
        });

        describe("current element is a card", () => {
            it("returns the next card", () => {
                const next_element = getElementDown(document, first_card);
                expect(next_element).toBe(second_card);
            });

            it("returns the add button if current one is the last card", () => {
                const next_element = getElementDown(document, second_card);
                expect(next_element).toBe(first_add_button);
            });

            it("throws if cell parent could not be found", () => {
                first_cell.dataset.navigation = "incorrect";
                expect(() => {
                    getElementDown(document, first_card);
                }).toThrow();
            });
        });

        it("returns the first card if current element is the add button", () => {
            const next_element = getElementDown(document, first_add_button);
            expect(next_element).toBe(first_card);
        });

        it("throws if data-navigation is incorrect", () => {
            first_card.dataset.navigation = "incorrect";
            expect(() => {
                getElementDown(document, first_card);
            }).toThrow();
        });
    });

    describe("getElementUp", () => {
        describe("current element is a swimlane", () => {
            it("returns the previous swimlane", () => {
                const previous_element = getElementUp(document, second_swimlane);
                expect(previous_element).toBe(first_swimlane);
            });

            it("returns the last swimlane if current one is the first", () => {
                const previous_element = getElementUp(document, first_swimlane);
                expect(previous_element).toBe(second_swimlane);
            });
        });

        describe("current element is a card", () => {
            it("returns the previous card", () => {
                const next_element = getElementUp(document, second_card);
                expect(next_element).toBe(first_card);
            });

            it("returns the add button if current one is the last card", () => {
                const next_element = getElementUp(document, first_card);
                expect(next_element).toBe(first_add_button);
            });

            it("throws if cell parent could not be found", () => {
                first_cell.dataset.navigation = "incorrect";
                expect(() => {
                    getElementUp(document, second_card);
                }).toThrow();
            });
        });

        it("returns the first card if current element is the add button", () => {
            const next_element = getElementUp(document, first_add_button);
            expect(next_element).toBe(second_card);
        });

        it("throws if data-navigation is incorrect", () => {
            second_card.dataset.navigation = "incorrect";
            expect(() => {
                getElementUp(document, second_card);
            }).toThrow();
        });
    });

    describe("getElementRight", () => {
        it("returns null if current element is neither a Card nor an AddForm", () => {
            const right_element = getElementRight(document, first_swimlane);
            expect(right_element).toBeNull();
        });

        it("returns the first card of the cell on the right", () => {
            const right_card = getElementRight(document, first_card);
            expect(right_card).toBe(third_card);
        });

        it("returns the first card of the first cell if in last cell", () => {
            const right_card = getElementRight(document, second_add_button);
            expect(right_card).toBe(first_card);
        });

        it("returns the add button of the cell on the right if empty", () => {
            const right_card = getElementRight(document, third_card);
            expect(right_card).toBe(second_add_button);
        });
    });

    describe("getElementLeft", () => {
        it("returns null if current element is neither a Card nor an AddForm", () => {
            const left_element = getElementLeft(document, first_swimlane);
            expect(left_element).toBeNull();
        });

        it("returns the first card of the cell on the left", () => {
            const left_card = getElementLeft(document, third_card);
            expect(left_card).toBe(first_card);
        });

        it("returns the add button of the last cell if in first cell and last cell is empty", () => {
            const left_card = getElementLeft(document, first_card);
            expect(left_card).toBe(second_add_button);
        });
    });

    function setupDocument(doc: Document): void {
        first_swimlane = doc.createElement("button"); //We use a buttons so it's easier to call focus on it
        first_swimlane.dataset.navigation = "swimlane";

        second_swimlane = doc.createElement("button");
        second_swimlane.dataset.navigation = "swimlane";

        first_cell = doc.createElement("div");
        first_cell.dataset.navigation = "cell";

        second_cell = doc.createElement("div");
        second_cell.dataset.navigation = "cell";

        third_cell = doc.createElement("div");
        third_cell.dataset.navigation = "cell";

        first_card = doc.createElement("button");
        first_card.dataset.navigation = "card";

        second_card = doc.createElement("button");
        second_card.dataset.navigation = "card";

        third_card = doc.createElement("button");
        third_card.dataset.navigation = "card";

        first_add_button = doc.createElement("button");
        first_add_button.dataset.navigation = "add-form";

        second_add_button = doc.createElement("button");
        second_add_button.dataset.navigation = "add-form";

        first_cell.append(first_card, second_card, first_add_button);
        second_cell.append(third_card);
        third_cell.append(second_add_button);

        first_swimlane.append(first_cell, second_cell, third_cell);
        doc.body.append(first_swimlane, second_swimlane);
    }
});
