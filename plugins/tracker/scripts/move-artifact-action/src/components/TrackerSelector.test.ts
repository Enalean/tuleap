/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import * as list_picker from "@tuleap/list-picker";
import type { ListPicker } from "@tuleap/list-picker/src";
import { createMoveModalLocalVue } from "../../tests/local-vue-for-tests";
import type { Tracker } from "../store/types";

import TrackerSelector from "./TrackerSelector.vue";

const getWrapper = async (store: Store): Promise<Wrapper<TrackerSelector>> =>
    shallowMount(TrackerSelector, {
        localVue: await createMoveModalLocalVue(),
        mocks: {
            $store: store,
        },
    });

const disabled_tracker: Tracker = {
    id: 10,
    label: "Tasks",
    disabled: true,
};

const tracker_list_with_disabled_from: Tracker[] = [
    disabled_tracker,
    {
        id: 11,
        label: "Epics",
        disabled: false,
    },
];

describe("TrackerSelector", () => {
    let store: Store, createListPicker: jest.SpyInstance, list_picker_instance: ListPicker;

    beforeEach(() => {
        list_picker_instance = {
            destroy: jest.fn(),
        };
        createListPicker = jest
            .spyOn(list_picker, "createListPicker")
            .mockReturnValue(list_picker_instance);

        store = createStoreMock({
            getters: {
                tracker_list_with_disabled_from,
            },
        });
    });

    it("should create a list-picker on its <select> input once mounted", async () => {
        await getWrapper(store);

        expect(createListPicker).toHaveBeenCalledTimes(1);
    });

    it(`Given that the displayed trackers come from the same project as the artifact to move
        Then the <select>'s label should have a title containing a warning`, async () => {
        const wrapper = await getWrapper(store);

        expect(wrapper.find("[data-test=tracker-selector-label]").attributes("title")).toBe(
            "An artifact cannot be moved in the same tracker"
        );
    });

    it(`Given that the displayed trackers come from a different project than the artifact to move
        Then the <select>'s label should not have a title containing a warning`, async () => {
        disabled_tracker.disabled = false;

        const wrapper = await getWrapper(store);

        expect(wrapper.find("[data-test=tracker-selector-label]").attributes("title")).toBe("");
    });

    it("the <select> should display the trackers", async () => {
        const wrapper = await getWrapper(store);
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=move-artifact-tracker-selector]"
        ).element;

        expect(select.options).toHaveLength(tracker_list_with_disabled_from.length);

        const select_options = Array.from(select.options);

        expect(select_options[0].value).toBe(String(tracker_list_with_disabled_from[0].id));
        expect(select_options[0].label).toBe(tracker_list_with_disabled_from[0].label);
        expect(select_options[0].disabled).toBe(tracker_list_with_disabled_from[0].disabled);

        expect(select_options[1].value).toBe(String(tracker_list_with_disabled_from[1].id));
        expect(select_options[1].label).toBe(tracker_list_with_disabled_from[1].label);
        expect(select_options[1].disabled).toBe(tracker_list_with_disabled_from[1].disabled);
    });

    it("When a tracker is selected, then the selected tracker's id should be commited", async () => {
        const wrapper = await getWrapper(store);
        const select_wrapper = wrapper.find<HTMLSelectElement>(
            "[data-test=move-artifact-tracker-selector]"
        );

        select_wrapper.element.selectedIndex = 1;
        select_wrapper.trigger("change");

        expect(store.commit).toHaveBeenCalledWith(
            "saveSelectedTrackerId",
            tracker_list_with_disabled_from[1].id
        );
    });

    it("When the component is about to be destroyed, then the list picker instance should be destroyed.", async () => {
        const wrapper = await getWrapper(store);

        wrapper.destroy();

        expect(list_picker_instance.destroy).toHaveBeenCalled();
    });
});
