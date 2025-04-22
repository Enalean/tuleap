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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import AddCard from "./AddCard.vue";
import LabelEditor from "../Editor/Label/LabelEditor.vue";
import AddButton from "./AddButton.vue";
import type { ColumnDefinition, Swimlane } from "../../../../../../type";
import type { NewCardPayload } from "../../../../../../store/swimlane/card/type";
import type { SwimlaneState } from "../../../../../../store/swimlane/type";
import CancelSaveButtons from "../EditMode/CancelSaveButtons.vue";
import { getGlobalTestOptions } from "../../../../../../helpers/global-options-for-test";

jest.useFakeTimers();

describe("AddCard", () => {
    const mock_is_adding_in_place = jest.fn();
    const mock_clear_is_adding_in_place = jest.fn();
    const mock_add_card = jest.fn();
    function getWrapper(
        swimlane_state: SwimlaneState = {} as SwimlaneState,
    ): VueWrapper<InstanceType<typeof AddCard>> {
        return shallowMount(AddCard, {
            props: {
                column: { id: 42 } as ColumnDefinition,
                swimlane: { card: { id: 69 } } as Swimlane,
                button_label: "",
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        swimlane: {
                            state: swimlane_state,
                            actions: {
                                addCard: mock_add_card,
                            },
                            namespaced: true,
                        },
                    },
                    mutations: {
                        setIsACellAddingInPlace: mock_is_adding_in_place,
                        clearIsACellAddingInPlace: mock_clear_is_adding_in_place,
                        setBacklogItemsHaveChildren: jest.fn(),
                    },
                }),
            },
        });
    }

    afterEach(() => {
        jest.clearAllMocks();
    });

    it("Displays add button and no editor yet", () => {
        const wrapper = getWrapper();

        expect(wrapper.findComponent(LabelEditor).exists()).toBe(false);
        expect(wrapper.findComponent(AddButton).exists()).toBe(true);
    });

    it("Given the button is clicked, then it shows the editor", async () => {
        const wrapper = getWrapper();

        wrapper.findComponent(AddButton).vm.$emit("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(LabelEditor).exists()).toBe(true);
        expect(wrapper.findComponent(LabelEditor).props("readonly")).toBe(false);
        expect(mock_is_adding_in_place).toHaveBeenCalled();
    });

    it("Given the cancel button is pressed, Then it displays back the button and hide the editor", async () => {
        const wrapper = getWrapper();

        wrapper.findComponent(AddButton).vm.$emit("click");
        await wrapper.vm.$nextTick();
        wrapper.findComponent(CancelSaveButtons).vm.$emit("cancel");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(LabelEditor).exists()).toBe(false);
        expect(wrapper.findComponent(AddButton).exists()).toBe(true);
        expect(mock_clear_is_adding_in_place).toHaveBeenCalled();
    });

    it(`Given the editor is displayed,
        And the user hits enter,
        Then the card is saved
        And the editor is cleared to enter a new card`, async () => {
        const wrapper = getWrapper();

        await wrapper.findComponent(AddButton).vm.$emit("click");
        wrapper.vm.label = "Lorem ipsum";

        expect(wrapper.vm.label).toBe("Lorem ipsum");
        wrapper.findComponent(LabelEditor).vm.$emit("save");

        expect(mock_add_card).toHaveBeenCalledWith(expect.anything(), {
            swimlane: wrapper.vm.$props.swimlane,
            column: wrapper.vm.$props.column,
            label: "Lorem ipsum",
        } as NewCardPayload);

        jest.spyOn(window, "scrollTo").mockImplementation();

        jest.runAllTimers();
        expect(wrapper.vm.label).toBe("");

        expect(wrapper.findComponent(LabelEditor).exists()).toBe(true);
    });

    it(`Given the editor is displayed,
        And the user clicks on the save button,
        Then the card is saved
        And the editor is cleared to enter a new card`, async () => {
        const wrapper = getWrapper();

        await wrapper.findComponent(AddButton).vm.$emit("click");
        wrapper.findComponent(LabelEditor).vm.$emit("input", "Lorem ipsum");

        expect(wrapper.vm.label).toBe("Lorem ipsum");
        wrapper.findComponent(CancelSaveButtons).vm.$emit("save");

        expect(mock_add_card).toHaveBeenCalledWith(expect.anything(), {
            swimlane: wrapper.vm.$props.swimlane,
            column: wrapper.vm.$props.column,
            label: "Lorem ipsum",
        } as NewCardPayload);

        jest.spyOn(window, "scrollTo").mockImplementation();

        jest.runAllTimers();
        expect(wrapper.vm.label).toBe("");

        expect(wrapper.findComponent(LabelEditor).exists()).toBe(true);
    });

    it(`Given the editor is displayed,
        And user didn't fill anything
        When the user clicks on the save button,
        Then save action is not performed`, async () => {
        const wrapper = getWrapper();

        await wrapper.findComponent(AddButton).vm.$emit("click");
        wrapper.findComponent(LabelEditor).vm.$emit("input", "");

        expect(wrapper.vm.label).toBe("");
        wrapper.findComponent(CancelSaveButtons).vm.$emit("save");

        expect(mock_add_card).not.toHaveBeenCalled();
    });

    it("Blocks the creation of a new card if one is ongoing", async () => {
        const wrapper = getWrapper({
            is_card_creation_blocked_due_to_ongoing_creation: true,
        } as SwimlaneState);
        await wrapper.findComponent(AddButton).vm.$emit("click");

        expect(wrapper.findComponent(LabelEditor).props("readonly")).toBe(true);
    });
});
