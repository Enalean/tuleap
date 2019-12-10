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

import { Card, Swimlane, Tracker } from "../../../type";
import * as mutations from "./card-mutations";
import { SwimlaneState } from "../type";
import { UpdateCardPayload, NewRemainingEffortPayload } from "./type";

jest.useFakeTimers();

describe(`Card mutations`, () => {
    describe("addCardToEditMode", () => {
        it("switch is_in_edit_mode to true", () => {
            const card: Card = { id: 123, is_in_edit_mode: false } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane]
            } as SwimlaneState;

            mutations.addCardToEditMode(state, card);

            expect(state.swimlanes[0].card.is_in_edit_mode).toBe(true);
        });
    });

    describe("removeCardFromEditMode", () => {
        it("switch is_in_edit_mode to false", () => {
            const card: Card = { id: 123, is_in_edit_mode: true } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane]
            } as SwimlaneState;

            mutations.removeCardFromEditMode(state, card);

            expect(state.swimlanes[0].card.is_in_edit_mode).toBe(false);
        });
    });

    describe("startSavingRemainingEffort", () => {
        it("switch is_being_saved to true", () => {
            const card: Card = { remaining_effort: { is_being_saved: false } } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane]
            } as SwimlaneState;

            mutations.startSavingRemainingEffort(state, card);

            expect.assertions(1);
            if (state.swimlanes[0].card.remaining_effort) {
                expect(state.swimlanes[0].card.remaining_effort.is_being_saved).toBe(true);
            }
        });
    });

    describe("resetSavingRemainingEffort", () => {
        it("switch is_being_saved and is_in_edit_mode to false", () => {
            const card: Card = {
                remaining_effort: { is_being_saved: true, is_in_edit_mode: true }
            } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane]
            } as SwimlaneState;

            mutations.resetSavingRemainingEffort(state, card);

            expect.assertions(2);
            if (state.swimlanes[0].card.remaining_effort) {
                expect(state.swimlanes[0].card.remaining_effort.is_being_saved).toBe(false);
                expect(state.swimlanes[0].card.remaining_effort.is_in_edit_mode).toBe(false);
            }
        });
    });

    describe("finishSavingRemainingEffort", () => {
        it("saves the new value and switch is_being_saved and is_in_edit_mode to false", () => {
            const card: Card = {
                remaining_effort: { value: 3.14, is_being_saved: true, is_in_edit_mode: true }
            } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane]
            } as SwimlaneState;
            const payload: NewRemainingEffortPayload = {
                card,
                value: 42
            };

            mutations.finishSavingRemainingEffort(state, payload);

            expect.assertions(3);
            if (state.swimlanes[0].card.remaining_effort) {
                expect(state.swimlanes[0].card.remaining_effort.value).toBe(42);
                expect(state.swimlanes[0].card.remaining_effort.is_being_saved).toBe(false);
                expect(state.swimlanes[0].card.remaining_effort.is_in_edit_mode).toBe(false);
            }
        });
    });

    describe("startSavingCard", () => {
        it("exits edit mode in order to save the card", () => {
            const card: Card = { is_being_saved: false, is_in_edit_mode: true } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane]
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
                swimlanes: [{ card } as Swimlane]
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
                is_just_saved: false
            } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane]
            } as SwimlaneState;
            const payload: UpdateCardPayload = { card, label: "Lorem", tracker: {} as Tracker };

            mutations.finishSavingCard(state, payload);

            expect(state.swimlanes[0].card.label).toBe("Lorem");
            expect(state.swimlanes[0].card.is_being_saved).toBe(false);
            expect(state.swimlanes[0].card.is_just_saved).toBe(true);

            jest.advanceTimersByTime(1000);

            expect(state.swimlanes[0].card.is_just_saved).toBe(false);
        });
    });

    describe("startCreatingCard", () => {
        it("Informs the store that the process of creating a new card has begun", () => {
            const state: SwimlaneState = {
                is_card_creation_blocked_due_to_ongoing_creation: false
            } as SwimlaneState;

            mutations.startCreatingCard(state);

            expect(state.is_card_creation_blocked_due_to_ongoing_creation).toBe(true);
        });
    });

    describe("cardIsHalfwayCreated", () => {
        it("Informs the store that the process of creating a new card is advanced enough to allow creation of another one", () => {
            const state: SwimlaneState = {
                is_card_creation_blocked_due_to_ongoing_creation: true
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
                is_just_saved: false
            } as Card;
            const state: SwimlaneState = {
                swimlanes: [{ card } as Swimlane],
                is_card_creation_blocked_due_to_ongoing_creation: true
            } as SwimlaneState;

            mutations.finishCreatingCard(state, card);

            expect(state.swimlanes[0].card.is_being_saved).toBe(false);
            expect(state.swimlanes[0].card.is_just_saved).toBe(true);

            jest.advanceTimersByTime(1000);

            expect(state.swimlanes[0].card.is_just_saved).toBe(false);
        });
    });
});
