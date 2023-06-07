/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { Card, Swimlane, Tracker, User } from "../../../type";
import * as mutations from "./card-mutations";
import type { SwimlaneState } from "../type";
import type { UpdateCardPayload, NewRemainingEffortPayload, UserForPeoplePicker } from "./type";

jest.useFakeTimers();

describe(`Card mutations`, () => {
    describe("addCardToEditMode", () => {
        it("switch is_in_edit_mode to true", () => {
            const card: Card = { id: 123, is_in_edit_mode: false } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane],
            } as SwimlaneState;

            mutations.addCardToEditMode(state, card);

            expect(state.swimlanes[0].card.is_in_edit_mode).toBe(true);
        });
    });

    describe("removeCardFromEditMode", () => {
        it("switch is_in_edit_mode to false", () => {
            const card: Card = { id: 123, is_in_edit_mode: true } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane],
            } as SwimlaneState;

            mutations.removeCardFromEditMode(state, card);

            expect(state.swimlanes[0].card.is_in_edit_mode).toBe(false);
        });
    });

    describe("startSavingRemainingEffort", () => {
        it(`saves the new value
            and switches is_being_saved to true
            and is_in_edit_mode to false`, () => {
            const card: Card = {
                remaining_effort: { value: 3.14, is_being_saved: false, is_in_edit_mode: true },
            } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane],
            } as SwimlaneState;
            const payload: NewRemainingEffortPayload = { card, value: 42 };

            mutations.startSavingRemainingEffort(state, payload);

            if (!state.swimlanes[0].card.remaining_effort) {
                throw new Error("Expected the first swimlane's card to have a remaining effort");
            }
            expect(state.swimlanes[0].card.remaining_effort.value).toBe(42);
            expect(state.swimlanes[0].card.remaining_effort.is_being_saved).toBe(true);
            expect(state.swimlanes[0].card.remaining_effort.is_in_edit_mode).toBe(false);
        });
    });

    describe("resetSavingRemainingEffort", () => {
        it("switches is_being_saved to false", () => {
            const card: Card = {
                remaining_effort: { is_being_saved: true, is_in_edit_mode: false },
            } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane],
            } as SwimlaneState;

            mutations.resetSavingRemainingEffort(state, card);

            if (!state.swimlanes[0].card.remaining_effort) {
                throw new Error("Expected the first swimlane's card to have a remaining effort");
            }
            expect(state.swimlanes[0].card.remaining_effort.is_being_saved).toBe(false);
            expect(state.swimlanes[0].card.remaining_effort.is_in_edit_mode).toBe(false);
        });
    });

    describe(`removeRemainingEffortFromEditMode`, () => {
        it(`switches is_in_edit_mode to false`, () => {
            const card = {
                remaining_effort: { is_in_edit_mode: true, is_being_saved: false },
            } as Card;
            const state = { swimlanes: [{ card } as Swimlane] } as SwimlaneState;

            mutations.removeRemainingEffortFromEditMode(state, card);

            if (!state.swimlanes[0].card.remaining_effort) {
                throw new Error("Expected the first swimlane's card to have a remaining effort");
            }
            const remaining_effort = state.swimlanes[0].card.remaining_effort;
            expect(remaining_effort.is_in_edit_mode).toBe(false);
            expect(remaining_effort.is_being_saved).toBe(false);
        });
    });

    describe("startSavingCard", () => {
        it("exits edit mode in order to save the card", () => {
            const card: Card = { is_being_saved: false, is_in_edit_mode: true } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane],
            } as SwimlaneState;

            mutations.startSavingCard(state, card);

            expect(state.swimlanes[0].card.is_being_saved).toBe(true);
            expect(state.swimlanes[0].card.is_in_edit_mode).toBe(false);
        });
    });

    describe("resetSavingCard", () => {
        it("switch is_being_saved to false", () => {
            const card: Card = { is_being_saved: true } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane],
            } as SwimlaneState;

            mutations.resetSavingCard(state, card);

            expect(state.swimlanes[0].card.is_being_saved).toBe(false);
        });
    });

    describe("finishSavingCard", () => {
        it("saves the new value and switch is_being_saved to false and informs that the card has just been saved", () => {
            const card: Card = {
                label: "Lorem ipsum",
                is_being_saved: true,
                is_just_saved: false,
                assignees: [{ id: 123 }],
            } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane],
            } as SwimlaneState;
            const payload: UpdateCardPayload = {
                card,
                label: "Lorem",
                tracker: {} as Tracker,
                assignees: [{ id: 234 }] as User[],
            };

            mutations.finishSavingCard(state, payload);

            expect(state.swimlanes[0].card.label).toBe("Lorem");
            expect(state.swimlanes[0].card.is_being_saved).toBe(false);
            expect(state.swimlanes[0].card.is_just_saved).toBe(true);
            expect(state.swimlanes[0].card.assignees[0].id).toBe(234);

            jest.advanceTimersByTime(1000);

            expect(state.swimlanes[0].card.is_just_saved).toBe(false);
        });
    });

    describe("startCreatingCard", () => {
        it("Informs the store that the process of creating a new card has begun", () => {
            const state: SwimlaneState = {
                is_card_creation_blocked_due_to_ongoing_creation: false,
            } as SwimlaneState;

            mutations.startCreatingCard(state);

            expect(state.is_card_creation_blocked_due_to_ongoing_creation).toBe(true);
        });
    });

    describe("cardIsHalfwayCreated", () => {
        it("Informs the store that the process of creating a new card is advanced enough to allow creation of another one", () => {
            const state: SwimlaneState = {
                is_card_creation_blocked_due_to_ongoing_creation: true,
            } as SwimlaneState;

            mutations.cardIsHalfwayCreated(state);

            expect(state.is_card_creation_blocked_due_to_ongoing_creation).toBe(false);
        });
    });

    describe("finishCreatingCard", () => {
        it("sets the saved flags on the card", () => {
            const card: Card = {
                label: "Lorem ipsum",
                is_being_saved: true,
                is_just_saved: false,
            } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane],
                is_card_creation_blocked_due_to_ongoing_creation: true,
            } as SwimlaneState;

            mutations.finishCreatingCard(state, card);

            expect(state.swimlanes[0].card.is_being_saved).toBe(false);
            expect(state.swimlanes[0].card.is_just_saved).toBe(true);

            jest.advanceTimersByTime(1000);

            expect(state.swimlanes[0].card.is_just_saved).toBe(false);
        });
    });

    describe("setPossibleAssigneesForFieldId", () => {
        it("Caches the assignees indexed by the assigned_to field id", () => {
            const state: SwimlaneState = {
                possible_assignees: new Map<number, UserForPeoplePicker[]>(),
            } as SwimlaneState;

            mutations.setPossibleAssigneesForFieldId(state, {
                assigned_to_field_id: 1234,
                users: [
                    { id: 1, display_name: "John" },
                    { id: 2, display_name: "Steeve" },
                    { id: 3, display_name: "Bob" },
                ] as UserForPeoplePicker[],
            });

            expect(state.possible_assignees).toEqual(
                new Map([
                    [
                        1234,
                        [
                            { id: 1, display_name: "John" },
                            { id: 2, display_name: "Steeve" },
                            { id: 3, display_name: "Bob" },
                        ],
                    ],
                ])
            );
        });
    });
});
