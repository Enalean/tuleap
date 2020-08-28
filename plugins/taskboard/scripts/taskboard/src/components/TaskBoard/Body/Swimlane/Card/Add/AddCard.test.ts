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

import { shallowMount, Wrapper } from "@vue/test-utils";
import AddCard from "./AddCard.vue";
import LabelEditor from "../Editor/Label/LabelEditor.vue";
import AddButton from "./AddButton.vue";
import { ColumnDefinition, Swimlane } from "../../../../../../type";
import { createStoreMock } from "../../../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../../../../../store/type";
import { NewCardPayload } from "../../../../../../store/swimlane/card/type";
import { SwimlaneState } from "../../../../../../store/swimlane/type";
import CancelSaveButtons from "../EditMode/CancelSaveButtons.vue";

jest.useFakeTimers();

function getWrapper(swimlane_state: SwimlaneState = {} as SwimlaneState): Wrapper<AddCard> {
    return shallowMount(AddCard, {
        propsData: {
            column: { id: 42 } as ColumnDefinition,
            swimlane: { card: { id: 69 } } as Swimlane,
        },
        mocks: {
            $store: createStoreMock({
                state: {
                    swimlane: swimlane_state,
                } as RootState,
            }),
        },
    });
}

describe("AddCard", () => {
    it("Displays add button and no editor yet", () => {
        const wrapper = getWrapper();

        expect(wrapper.findComponent(LabelEditor).exists()).toBe(false);
        expect(wrapper.findComponent(AddButton).exists()).toBe(true);
    });

    it("Given the button is clicked, Then it hides the button and show the editor", async () => {
        const wrapper = getWrapper();

        wrapper.findComponent(AddButton).vm.$emit("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(LabelEditor).exists()).toBe(true);
        expect(wrapper.findComponent(LabelEditor).props("readonly")).toBe(false);
        expect(wrapper.findComponent(AddButton).exists()).toBe(false);
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setIsACellAddingInPlace");
    });

    it("Given the cancel button is pressed, Then it displays back the button and hide the editor", async () => {
        const wrapper = getWrapper();

        wrapper.findComponent(AddButton).vm.$emit("click");
        await wrapper.vm.$nextTick();
        wrapper.findComponent(CancelSaveButtons).vm.$emit("cancel");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(LabelEditor).exists()).toBe(false);
        expect(wrapper.findComponent(AddButton).exists()).toBe(true);
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("clearIsACellAddingInPlace");
    });

    it(`Given the editor is displayed,
        And the user hits enter,
        Then the card is saved
        And the editor is cleared to enter a new card`, async () => {
        const wrapper = getWrapper();

        wrapper.findComponent(AddButton).vm.$emit("click");
        wrapper.setData({ label: "Lorem ipsum" });
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.label).toBe("Lorem ipsum");
        wrapper.findComponent(LabelEditor).vm.$emit("save");

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("swimlane/addCard", {
            swimlane: wrapper.vm.$props.swimlane,
            column: wrapper.vm.$props.column,
            label: "Lorem ipsum",
        } as NewCardPayload);

        jest.spyOn(window, "scrollTo").mockImplementation();

        jest.runAllTimers();
        expect(wrapper.vm.$data.label).toBe("");

        expect(wrapper.findComponent(LabelEditor).exists()).toBe(true);
        expect(wrapper.findComponent(AddButton).exists()).toBe(false);
    });

    it(`Given the editor is displayed,
        And the user clicks on the save button,
        Then the card is saved
        And the editor is cleared to enter a new card`, async () => {
        const wrapper = getWrapper();

        wrapper.findComponent(AddButton).vm.$emit("click");
        wrapper.setData({ label: "Lorem ipsum" });
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.label).toBe("Lorem ipsum");
        wrapper.findComponent(CancelSaveButtons).vm.$emit("save");

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("swimlane/addCard", {
            swimlane: wrapper.vm.$props.swimlane,
            column: wrapper.vm.$props.column,
            label: "Lorem ipsum",
        } as NewCardPayload);

        jest.spyOn(window, "scrollTo").mockImplementation();

        jest.runAllTimers();
        expect(wrapper.vm.$data.label).toBe("");

        expect(wrapper.findComponent(LabelEditor).exists()).toBe(true);
        expect(wrapper.findComponent(AddButton).exists()).toBe(false);
    });

    it(`Given the editor is displayed,
        And user didn't fill anything
        When the user clicks on the save button,
        Then save action is not performed`, async () => {
        const wrapper = getWrapper();

        wrapper.findComponent(AddButton).vm.$emit("click");
        wrapper.setData({ label: "" });
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.label).toBe("");
        wrapper.findComponent(CancelSaveButtons).vm.$emit("save");

        expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalled();
    });

    it("Blocks the creation of a new card if one is ongoing", async () => {
        const wrapper = getWrapper({
            is_card_creation_blocked_due_to_ongoing_creation: true,
        } as SwimlaneState);
        wrapper.findComponent(AddButton).vm.$emit("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(LabelEditor).props("readonly")).toBe(true);
    });
});
