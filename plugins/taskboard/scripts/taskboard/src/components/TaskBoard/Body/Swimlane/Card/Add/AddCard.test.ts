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
import EventBus from "../../../../../../helpers/event-bus";
import { Card, ColumnDefinition, TaskboardEvent } from "../../../../../../type";
import { createStoreMock } from "../../../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../../../../../store/type";
import { NewCardPayload } from "../../../../../../store/swimlane/card/type";

jest.useFakeTimers();

function getWrapper(): Wrapper<AddCard> {
    return shallowMount(AddCard, {
        propsData: {
            column: { id: 42 } as ColumnDefinition,
            parent: { id: 69 } as Card
        },
        mocks: {
            $store: createStoreMock({
                state: {
                    swimlane: {}
                } as RootState
            })
        }
    });
}

describe("AddCard", () => {
    it("Displays add button and no editor yet", () => {
        const wrapper = getWrapper();

        expect(wrapper.contains(LabelEditor)).toBe(false);
        expect(wrapper.contains(AddButton)).toBe(true);
    });

    it("Given the button is clicked, Then it hides the button and show the editor ", () => {
        const wrapper = getWrapper();

        wrapper.find(AddButton).vm.$emit("click");

        expect(wrapper.contains(LabelEditor)).toBe(true);
        expect(wrapper.contains(AddButton)).toBe(false);
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setIsACellAddingInPlace");
    });

    it("Given the esc key is pressed, Then it displays back the button and hide the editor", () => {
        const wrapper = getWrapper();

        wrapper.find(AddButton).vm.$emit("click");
        EventBus.$emit(TaskboardEvent.ESC_KEY_PRESSED);

        expect(wrapper.contains(LabelEditor)).toBe(false);
        expect(wrapper.contains(AddButton)).toBe(true);
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("clearIsACellAddingInPlace");
    });

    it("Given the esc key is pressed while the editor was not in edit mode, Then it does trigger any mutation", () => {
        const wrapper = getWrapper();

        EventBus.$emit(TaskboardEvent.ESC_KEY_PRESSED);

        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`Given the editor is displayed,
        And the user hits enter,
        Then the card is saved
        And the editor is cleared to enter a new card.
        `, () => {
        const wrapper = getWrapper();

        wrapper.find(AddButton).vm.$emit("click");
        wrapper.setData({ label: "Lorem ipsum" });

        expect(wrapper.vm.$data.label).toBe("Lorem ipsum");
        wrapper.find(LabelEditor).vm.$emit("save");

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("swimlane/addCard", {
            parent: wrapper.vm.$props.parent,
            column: wrapper.vm.$props.column,
            label: "Lorem ipsum"
        } as NewCardPayload);

        jest.runAllTimers();
        expect(wrapper.vm.$data.label).toBe("");

        expect(wrapper.contains(LabelEditor)).toBe(true);
        expect(wrapper.contains(AddButton)).toBe(false);
    });
});
