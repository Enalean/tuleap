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

import { State } from "../../../../../store/type";
import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { createTrackerCreationLocalVue } from "../../../../../helpers/local-vue-for-tests";
import TrackerTemplateSelector from "./TrackerTemplateSelector.vue";

describe("TrackerTemplateSelector", () => {
    async function getWrapper(
        state: State = {} as State
    ): Promise<Wrapper<TrackerTemplateSelector>> {
        return shallowMount(TrackerTemplateSelector, {
            mocks: {
                $store: createStoreMock({
                    state
                })
            },
            localVue: await createTrackerCreationLocalVue()
        });
    }

    it("Stores the selected template tracker id each time the user selects a template", async () => {
        const wrapper = await getWrapper({
            project_templates: [
                {
                    project_name: "Scrum template",
                    tracker_list: [
                        { id: "10", name: "Bug" },
                        { id: "11", name: "Stories" },
                        { id: "12", name: "Releases" }
                    ]
                }
            ]
        } as State);

        wrapper.find("[data-test=template-selector]").setValue("10");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setSelectedTemplateTrackerId", "10");

        wrapper.find("[data-test=template-selector]").setValue("11");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setSelectedTemplateTrackerId", "11");
    });
});
