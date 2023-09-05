/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import * as drag_drop from "./drag-drop";
import { createElement } from "./jest/create-dom-element";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { VueGettextProvider } from "./vue-gettext-provider";
import type { Store } from "vuex";
import type { State } from "../type";
import { getFeatureInProgramIncrement } from "../store/getters";

describe(`drag-drop helper`, () => {
    describe(`isContainer()`, () => {
        it(`Given an element without a data-is-container flag, it will return false`, () => {
            const element = createElement();

            expect(drag_drop.isContainer(element)).toBe(false);
        });

        it(`Given a taskboard cell with a data-is-container flag, it will return true`, () => {
            const element = createElement();
            element.setAttribute("data-is-container", "true");

            expect(drag_drop.isContainer(element)).toBe(true);
        });
    });

    describe(`canMove()`, () => {
        it(`Given an element with no draggable attribute, it will return false`, () => {
            const element = createElement();

            expect(drag_drop.canMove(element)).toBe(false);
        });

        it(`Given a element with a draggable flag, it will return true`, () => {
            const element = createElement();
            element.setAttribute("draggable", "true");

            expect(drag_drop.canMove(element)).toBe(true);
        });
    });

    describe("invalid", () => {
        it(`Given a handle with a not-drag-handle flag, it will return true`, () => {
            const handle = createElement();
            handle.setAttribute("data-not-drag-handle", "true");

            expect(drag_drop.invalid(handle)).toBe(true);
        });

        it(`Given a handle whose a parent has a not-drag-handle flag, it will return true`, () => {
            const handle = createElement();
            const parent = createElement();
            handle.setAttribute("data-not-drag-handle", "true");
            parent.appendChild(handle);

            expect(drag_drop.invalid(handle)).toBe(true);
        });

        it(`Given a regular handle, it will return false`, () => {
            const handle = createElement("taskboard-stuff");
            expect(drag_drop.invalid(handle)).toBe(false);
        });
    });

    describe(`checkAcceptsDrop()`, () => {
        let store: Store<State>, state: State;
        const gettext_provider: VueGettextProvider = {
            $gettext: (s: string) => s,
        };

        beforeEach(() => {
            store = createStoreMock({
                state,
                getters: {
                    getFeatureInProgramIncrement: getFeatureInProgramIncrement(state),
                },
            }) as unknown as Store<State>;
        });

        it(`Given can plan attribute is not provided, Then the drop is rejected`, () => {
            const dropped_card = createElement();
            const source_cell = createElement();
            const target_cell = source_cell;

            expect(
                drag_drop.checkAcceptsDrop(store, gettext_provider, {
                    dropped_card,
                    target_cell,
                    source_cell,
                }),
            ).toBe(false);
        });

        it(`Given user can not plan and given zone does not have a message, Then the drop is rejected`, () => {
            const dropped_card = createElement();
            const source_cell = createElement();
            const target_cell = source_cell;

            expect(
                drag_drop.checkAcceptsDrop(store, gettext_provider, {
                    dropped_card,
                    target_cell,
                    source_cell,
                }),
            ).toBe(false);
        });

        it(`Given user can not plan and given zone have an error message, Then the drop is rejected and message is displayed`, () => {
            const dropped_card = createElement();
            const source_cell = createElement();
            const target_cell = source_cell;

            const error_message_element = createElement("drop-not-accepted-overlay");
            const error_message = document.createElement("p");
            error_message_element.appendChild(error_message);
            target_cell.appendChild(error_message_element);

            expect(
                drag_drop.checkAcceptsDrop(store, gettext_provider, {
                    dropped_card,
                    target_cell,
                    source_cell,
                }),
            ).toBe(false);
            expect(error_message_element.classList).toContain("drop-not-accepted");
            expect(error_message.textContent).toBe(
                "You are not allowed to plan in this program increment.",
            );
        });

        it("Giver user can plan and a feature planned with user stories planned, Then the drop is rejected when the target is not the same PI", () => {
            state = {
                program_increments: [
                    { id: 14, features: [{ id: 1, has_user_story_planned: true }] },
                ],
            } as State;
            store = createStoreMock({
                state,
                getters: {
                    getFeatureInProgramIncrement: getFeatureInProgramIncrement(state),
                },
            }) as unknown as Store<State>;

            const dropped_card = createElement();
            dropped_card.setAttribute("data-element-id", "1");
            const source_cell = createElement();
            source_cell.setAttribute("data-program-increment-id", "14");
            const target_cell = createElement();
            target_cell.setAttribute("data-program-increment-id", "666");
            target_cell.setAttribute("data-can-plan", "true");

            const error_message_element = createElement("drop-not-accepted-overlay");
            const error_message = document.createElement("p");
            error_message_element.appendChild(error_message);
            target_cell.appendChild(error_message_element);

            expect(
                drag_drop.checkAcceptsDrop(store, gettext_provider, {
                    dropped_card,
                    target_cell,
                    source_cell,
                }),
            ).toBe(false);
            expect(error_message_element.classList).toContain("drop-not-accepted");
            expect(error_message.textContent).toBe(
                "The feature has elements planned in team project, it can not be unplanned.",
            );
        });

        it("Giver user can plan and a feature planned with user stories planned, Then the drop is rejected when the target is the backlog", () => {
            state = {
                program_increments: [
                    { id: 14, features: [{ id: 1, has_user_story_planned: true }] },
                ],
            } as State;
            store = createStoreMock({
                state,
                getters: {
                    getFeatureInProgramIncrement: getFeatureInProgramIncrement(state),
                },
            }) as unknown as Store<State>;

            const dropped_card = createElement();
            dropped_card.setAttribute("data-element-id", "1");
            const source_cell = createElement();
            source_cell.setAttribute("data-program-increment-id", "14");
            const target_cell = createElement();
            target_cell.setAttribute("data-can-plan", "true");

            const error_message_element = createElement("drop-not-accepted-overlay");
            const error_message = document.createElement("p");
            error_message_element.appendChild(error_message);
            target_cell.appendChild(error_message_element);

            expect(
                drag_drop.checkAcceptsDrop(store, gettext_provider, {
                    dropped_card,
                    target_cell,
                    source_cell,
                }),
            ).toBe(false);
            expect(error_message_element.classList).toContain("drop-not-accepted");
            expect(error_message.textContent).toBe(
                "The feature has elements planned in team project, it can not be unplanned.",
            );
        });

        it("Giver user can plan and a feature planned with user stories planned, Then the drop is accepted when the target is the same PI", () => {
            state = {
                program_increments: [
                    { id: 14, features: [{ id: 1, has_user_story_planned: true }] },
                ],
            } as State;
            store = createStoreMock({
                state,
                getters: {
                    getFeatureInProgramIncrement: getFeatureInProgramIncrement(state),
                },
            }) as unknown as Store<State>;

            const dropped_card = createElement();
            dropped_card.setAttribute("data-element-id", "1");
            const source_cell = createElement();
            source_cell.setAttribute("data-program-increment-id", "14");
            const target_cell = createElement();
            target_cell.setAttribute("data-program-increment-id", "14");
            target_cell.setAttribute("data-can-plan", "true");

            const error_message = createElement("drop-not-accepted-overlay");
            target_cell.appendChild(error_message);

            expect(
                drag_drop.checkAcceptsDrop(store, gettext_provider, {
                    dropped_card,
                    target_cell,
                    source_cell,
                }),
            ).toBe(true);
        });

        it(`Given user can plan and given zone have an error message, Then the drop is accepted`, () => {
            const dropped_card = createElement();
            const source_cell = createElement();
            const target_cell = source_cell;
            target_cell.setAttribute("data-can-plan", "true");

            expect(
                drag_drop.checkAcceptsDrop(store, gettext_provider, {
                    dropped_card,
                    target_cell,
                    source_cell,
                }),
            ).toBe(true);
        });
    });
});
