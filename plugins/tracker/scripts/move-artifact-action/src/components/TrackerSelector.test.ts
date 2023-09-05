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

import { vi, describe, beforeEach, it, expect } from "vitest";
import type { SpyInstance } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import * as list_picker from "@tuleap/list-picker";
import type { ListPicker } from "@tuleap/list-picker";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { useSelectorsStore } from "../stores/selectors";
import type { Tracker } from "../api/types";
import TrackerSelector from "./TrackerSelector.vue";
import { TRACKER_ID } from "../injection-symbols";

const current_tracker_id = 10;
const trackers: Tracker[] = [
    {
        id: current_tracker_id,
        label: "Tasks",
    },
    {
        id: 11,
        label: "Epics",
    },
];

vi.mock("@tuleap/vue-strict-inject");

describe("TrackerSelector", () => {
    let createListPicker: SpyInstance, list_picker_instance: ListPicker;

    const getWrapper = (tracker_id: number): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            if (key !== TRACKER_ID) {
                throw new Error(`Tried to inject ${key} while it was not mocked.`);
            }

            return tracker_id;
        });

        return shallowMount(TrackerSelector, {
            global: {
                ...getGlobalTestOptions({
                    initialState: {
                        selectors: {
                            trackers,
                        },
                    },
                }),
            },
        });
    };

    beforeEach(() => {
        list_picker_instance = {
            destroy: vi.fn(),
        };
        createListPicker = vi
            .spyOn(list_picker, "createListPicker")
            .mockReturnValue(list_picker_instance);
    });

    it("should create a list-picker on its <select> input once mounted", () => {
        getWrapper(current_tracker_id);

        expect(createListPicker).toHaveBeenCalledTimes(1);
    });

    it(`Given that the displayed trackers come from the same project as the artifact to move
        Then the <select>'s label should have a title containing a warning`, () => {
        const wrapper = getWrapper(current_tracker_id);

        expect(wrapper.find("[data-test=tracker-selector-label]").attributes("title")).toBe(
            "An artifact cannot be moved in the same tracker",
        );
    });

    it(`Given that the displayed trackers come from a different project than the artifact to move
        Then the <select>'s label should not have a title containing a warning`, () => {
        const wrapper = getWrapper(current_tracker_id + 10);

        expect(wrapper.find("[data-test=tracker-selector-label]").attributes("title")).toBe("");
    });

    it("the <select> should display the trackers", () => {
        const wrapper = getWrapper(current_tracker_id);
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=move-artifact-tracker-selector]",
        ).element;

        expect(select.options).toHaveLength(trackers.length);

        const select_options = Array.from(select.options);

        expect(select_options[0].value).toBe(String(trackers[0].id));
        expect(select_options[0].label).toBe(trackers[0].label);
        expect(select_options[0].disabled).toBe(true);

        expect(select_options[1].value).toBe(String(trackers[1].id));
        expect(select_options[1].label).toBe(trackers[1].label);
        expect(select_options[1].disabled).toBe(false);
    });

    it("When a tracker is selected, then the selected tracker's id should be commited", () => {
        const wrapper = getWrapper(current_tracker_id);
        const select_wrapper = wrapper.find<HTMLSelectElement>(
            "[data-test=move-artifact-tracker-selector]",
        );

        select_wrapper.element.selectedIndex = 1;
        select_wrapper.trigger("change");

        expect(useSelectorsStore().saveSelectedTrackerId).toHaveBeenCalledWith(trackers[1].id);
    });

    it("When the component is about to be destroyed, then the list picker instance should be destroyed.", () => {
        const wrapper = getWrapper(current_tracker_id);

        wrapper.unmount();

        expect(list_picker_instance.destroy).toHaveBeenCalled();
    });
});
