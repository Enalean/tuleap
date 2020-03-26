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

import { shallowMount, Slots, Wrapper } from "@vue/test-utils";
import BaseCard from "./BaseCard.vue";
import { createStoreMock } from "../../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { Card, TaskboardEvent, Tracker, User } from "../../../../../type";
import EventBus from "../../../../../helpers/event-bus";
import LabelEditor from "./Editor/Label/LabelEditor.vue";
import { UpdateCardPayload } from "../../../../../store/swimlane/card/type";
import * as scroll_helper from "../../../../../helpers/scroll-to-item";

function getWrapper(
    card: Card,
    slots: Slots = {},
    user_has_accessibility_mode = false,
    tracker_of_card: Tracker = { title_field: { id: 1212 } } as Tracker
): Wrapper<BaseCard> {
    return shallowMount(BaseCard, {
        mocks: {
            $store: createStoreMock({
                state: {
                    user: { user_has_accessibility_mode },
                    swimlane: {},
                    fullscreen: {},
                },
                getters: {
                    tracker_of_card: (): Tracker => tracker_of_card,
                },
            }),
        },
        propsData: { card },
        slots,
    });
}

function getCard(
    definition: Card = {
        background_color: "",
        is_in_edit_mode: false,
    } as Card
): Card {
    return {
        id: 43,
        color: "lake-placid-blue",
        assignees: [] as User[],
        ...definition,
    } as Card;
}

describe("BaseCard", () => {
    it("doesn't add a dummy taskboard-card-background- class if the card has no background color", () => {
        const wrapper = getWrapper(getCard());

        expect(wrapper.classes()).not.toContain("taskboard-card-background-");
    });

    it("adds accessibility class if user needs it and card has a background color", () => {
        const wrapper = getWrapper(getCard({ background_color: "fiesta-red" } as Card), {}, true);

        expect(wrapper.contains(".taskboard-card-accessibility")).toBe(true);
        expect(wrapper.classes()).toContain("taskboard-card-with-accessibility");
    });

    it("does not add accessibility class if user needs it but card has no background color", () => {
        const wrapper = getWrapper(getCard(), {}, true);

        expect(wrapper.contains(".taskboard-card-accessibility")).toBe(false);
        expect(wrapper.classes()).not.toContain("taskboard-card-with-accessibility");
    });

    it("includes the remaining effort slot", () => {
        const wrapper = getWrapper(getCard(), {
            remaining_effort: '<div class="my-remaining-effort"></div>',
        });

        expect(wrapper.contains(".taskboard-card > .my-remaining-effort")).toBe(true);
    });

    describe("edit mode", () => {
        beforeEach(() => {
            jest.clearAllMocks();
        });

        it("Given the card is in read mode, then it doesn't add additional class", () => {
            const wrapper = getWrapper(getCard({ is_in_edit_mode: false } as Card));

            expect(wrapper.classes("taskboard-card-edit-mode")).toBe(false);
        });

        it("Given the card is in edit mode, then it adds necessary class", () => {
            const wrapper = getWrapper(getCard({ is_in_edit_mode: true } as Card));

            expect(wrapper.classes("taskboard-card-edit-mode")).toBe(true);
        });

        it("Given the card is in read mode, when user clicks on the trigger pencil, then it toggles its edit mode", () => {
            const card = getCard({ is_in_edit_mode: false } as Card);
            const wrapper = getWrapper(card);

            wrapper.get("[data-test=card-edit-button]").trigger("click");

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "swimlane/addCardToEditMode",
                card
            );
        });

        it("Given the card is in edit mode, when user clicks on it, then it does nothing", () => {
            const card = getCard({ is_in_edit_mode: true } as Card);
            const wrapper = getWrapper(card);

            wrapper.get("[data-test=card-edit-button]").trigger("click");
            expect(wrapper.vm.$store.commit).not.toHaveBeenCalledWith(
                "swimlane/addCardToEditMode",
                expect.any(Object)
            );
        });

        it(`Given the user has not the permission to edit the card title,
            Or the semantic title of the tracker is not set
            Then it won't display the edit mode trigger button`, () => {
            const card = getCard();
            const wrapper = getWrapper(card, {}, false, { title_field: null } as Tracker);

            expect(wrapper.contains(".taskboard-card-edit-trigger")).toBe(false);
            expect(wrapper.classes("taskboard-card-editable")).toBe(false);
        });

        it(`Given the user has the permission to edit the card title
            Then it will display the card as editable`, () => {
            const wrapper = getWrapper(getCard());

            expect(wrapper.contains(".taskboard-card-edit-trigger")).toBe(true);
            expect(wrapper.classes("taskboard-card-editable")).toBe(true);
        });

        it(`Given the user has the permission to edit the card title
            And the card is being saved
            Then it won't display the card as editable`, () => {
            const wrapper = getWrapper(getCard({ is_being_saved: true } as Card));

            expect(wrapper.contains(".taskboard-card-edit-trigger")).toBe(false);
            expect(wrapper.classes("taskboard-card-editable")).toBe(false);
        });

        it(`Cancels the edition of the card if user clicks on cancel button (that is outside of this component)`, () => {
            const card = getCard({ is_in_edit_mode: true } as Card);
            const wrapper = getWrapper(card);

            EventBus.$emit(TaskboardEvent.CANCEL_CARD_EDITION, card);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "swimlane/removeCardFromEditMode",
                card
            );
        });

        it(`Reset the label to the former value if user hits Cancel`, () => {
            const card = getCard({ label: "Lorem", is_in_edit_mode: true } as Card);
            const wrapper = getWrapper(card);

            wrapper.setData({ label: "Ipsum" });
            expect(wrapper.vm.$data.label).toBe("Ipsum");
            EventBus.$emit(TaskboardEvent.CANCEL_CARD_EDITION, card);
            expect(wrapper.vm.$data.label).toBe("Lorem");
        });

        it(`Saves the new label when user hits enter`, () => {
            const card = getCard({ label: "toto", is_in_edit_mode: true } as Card);
            const wrapper = getWrapper(card);

            const label = "Lorem ipsum";
            wrapper.setData({ label });
            const edit_label = wrapper.get(LabelEditor);
            edit_label.vm.$emit("save");

            expect(wrapper.vm.$store.commit).not.toHaveBeenCalledWith(
                "swimlane/removeCardFromEditMode",
                card
            );
            expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("swimlane/saveCard", {
                card,
                label,
                tracker: { title_field: { id: 1212 } } as Tracker,
                assignees: [],
            } as UpdateCardPayload);
        });

        it(`Saves the new label when user clicks on save button`, () => {
            const card = getCard({ label: "toto", is_in_edit_mode: true } as Card);
            const wrapper = getWrapper(card);

            const label = "Lorem ipsum";
            wrapper.setData({ label });

            EventBus.$emit(TaskboardEvent.SAVE_CARD_EDITION, card);

            expect(wrapper.vm.$store.commit).not.toHaveBeenCalledWith(
                "swimlane/removeCardFromEditMode",
                card
            );
            expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("swimlane/saveCard", {
                card,
                label,
                tracker: { title_field: { id: 1212 } } as Tracker,
                assignees: [],
            } as UpdateCardPayload);
        });

        it(`Does not save the card if new label and assignees are identical to the former ones`, () => {
            const card = getCard({ label: "toto", is_in_edit_mode: true } as Card);
            const wrapper = getWrapper(card);

            wrapper.setData({ label: "toto" });
            const edit_label = wrapper.get(LabelEditor);
            edit_label.vm.$emit("save");

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "swimlane/removeCardFromEditMode",
                card
            );
            expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalled();
        });

        it(`Save the card if label is identical to the former one but assignees are not`, () => {
            const card = getCard({ label: "toto", is_in_edit_mode: true } as Card);
            const wrapper = getWrapper(card);

            wrapper.setData({ label: "toto" });
            wrapper.setData({ assignees: [{ id: 123 }, { id: 234 }] });
            const edit_label = wrapper.get(LabelEditor);
            edit_label.vm.$emit("save");

            expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("swimlane/saveCard", {
                card,
                label: "toto",
                tracker: { title_field: { id: 1212 } } as Tracker,
                assignees: [{ id: 123 }, { id: 234 }],
            } as UpdateCardPayload);
        });

        it("displays a card in edit mode", () => {
            const card = getCard({
                is_in_edit_mode: true,
                is_being_saved: true,
                is_just_saved: true,
            } as Card);
            const wrapper = getWrapper(card);

            expect(wrapper.classes()).toContain("taskboard-card-edit-mode");
            expect(wrapper.classes()).not.toContain("taskboard-card-is-being-saved");
            expect(wrapper.classes()).not.toContain("taskboard-card-is-just-saved");
        });

        it("displays a card as being saved", () => {
            const card = getCard({
                is_in_edit_mode: false,
                is_being_saved: true,
                is_just_saved: true,
            } as Card);
            const wrapper = getWrapper(card);

            expect(wrapper.classes()).not.toContain("taskboard-card-edit-mode");
            expect(wrapper.classes()).toContain("taskboard-card-is-being-saved");
            expect(wrapper.classes()).not.toContain("taskboard-card-is-just-saved");
        });

        it("displays a card as being just saved", () => {
            const card = getCard({
                is_in_edit_mode: false,
                is_being_saved: false,
                is_just_saved: true,
            } as Card);
            const wrapper = getWrapper(card);

            expect(wrapper.classes()).not.toContain("taskboard-card-edit-mode");
            expect(wrapper.classes()).not.toContain("taskboard-card-is-being-saved");
            expect(wrapper.classes()).toContain("taskboard-card-is-just-saved");
        });

        it("scrolls to the card when it is ouside the viewport in edit mode", () => {
            const card = getCard({
                is_in_edit_mode: false,
                is_being_saved: false,
                is_just_saved: true,
            } as Card);

            jest.useFakeTimers();

            const wrapper = getWrapper(card);

            jest.spyOn(scroll_helper, "scrollToItemIfNeeded").mockImplementation();

            wrapper.get("[data-test=card-edit-button]").trigger("click");

            jest.runAllTimers();
            expect(scroll_helper.scrollToItemIfNeeded).toHaveBeenCalled();
        });
    });
});
