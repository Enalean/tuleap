/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { State } from "../../../../../store/type";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-tests";
import TrackerTemplateSelector from "./TrackerTemplateSelector.vue";

describe("TrackerTemplateSelector", () => {
    let mock_set_selected_tracker_template: jest.Mock;

    beforeEach(() => {
        mock_set_selected_tracker_template = jest.fn();
    });

    function getWrapper(state: State = {} as State): VueWrapper {
        return shallowMount(TrackerTemplateSelector, {
            global: {
                ...getGlobalTestOptions({
                    state,
                    mutations: {
                        setSelectedTrackerTemplate: mock_set_selected_tracker_template,
                    },
                }),
            },
        });
    }

    it("Stores the selected template tracker id each time the user selects a template", () => {
        const wrapper = getWrapper({
            project_templates: [
                {
                    project_name: "Scrum template",
                    tracker_list: [
                        { id: "10", name: "Bug" },
                        { id: "11", name: "Stories" },
                        { id: "12", name: "Releases" },
                    ],
                },
            ],
        } as State);

        wrapper.get("[data-test=template-selector]").setValue("10");
        expect(mock_set_selected_tracker_template).toHaveBeenCalledWith(expect.anything(), "10");

        wrapper.get("[data-test=template-selector]").setValue("11");
        expect(mock_set_selected_tracker_template).toHaveBeenCalledWith(expect.anything(), "11");
    });

    it(`pre-selects the current selected template if any,
        so it keeps showing the selected template when user goes back to step 1`, async () => {
        const story_tracker = { id: "11", name: "Stories" };
        const wrapper = getWrapper({
            project_templates: [
                {
                    project_name: "Scrum template",
                    tracker_list: [
                        { id: "10", name: "Bug" },
                        story_tracker,
                        { id: "12", name: "Releases" },
                    ],
                },
            ],
            selected_tracker_template: story_tracker,
        } as State);

        const selectbox = wrapper.get<HTMLInputElement>("[data-test=template-selector]").element;
        await wrapper.vm.$nextTick();

        expect(selectbox.value).toStrictEqual(story_tracker.id);
    });
});
