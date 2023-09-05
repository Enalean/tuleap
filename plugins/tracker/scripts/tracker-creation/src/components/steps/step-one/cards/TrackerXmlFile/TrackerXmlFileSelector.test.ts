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

import TrackerXmlFileSelector from "./TrackerXmlFileSelector.vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createTrackerCreationLocalVue } from "../../../../../helpers/local-vue-for-tests";
import type { State } from "../../../../../store/type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("TrackerXmlFileSelector", () => {
    async function getWrapper(
        state: State = {} as State,
    ): Promise<Wrapper<TrackerXmlFileSelector>> {
        const wrapper = shallowMount(TrackerXmlFileSelector, {
            mocks: {
                $store: createStoreMock({
                    state,
                }),
            },
            localVue: await createTrackerCreationLocalVue(),
        });

        jest.spyOn(wrapper.vm.$el, "appendChild");

        return wrapper;
    }

    it("renders a fresh input[type=file] when no selection has been made before", async () => {
        const wrapper = await getWrapper({
            selected_xml_file_input: null,
        } as State);

        const file_input = wrapper.find("[data-test=tracker-creation-xml-file-selector]");

        expect(file_input.exists()).toBe(true);

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setSelectedTrackerXmlFileInput",
            file_input.element,
        );
        expect(wrapper.vm.$el.appendChild).not.toHaveBeenCalled();
    });

    it("renders the stored input when a selection has been made before", async () => {
        const local_document = document.implementation.createHTMLDocument();
        const file_input = local_document.createElement("input");

        file_input.setAttribute("type", "file");
        file_input.setAttribute("data-test", "injected-file-input");

        const state: State = {
            selected_xml_file_input: file_input,
        } as State;

        const wrapper = await getWrapper(state);

        expect(wrapper.find("[data-test=tracker-creation-xml-file-selector]").exists()).toBe(false);
        expect(wrapper.find("[data-test=injected-file-input]").exists()).toBe(true);
    });

    it("Displays an error when the provided file is not parsable", async () => {
        const local_document = document.implementation.createHTMLDocument();
        const file_input = local_document.createElement("input");

        file_input.setAttribute("type", "file");
        file_input.setAttribute("data-test", "injected-file-input");

        const state: State = {
            selected_xml_file_input: file_input,
            has_xml_file_error: true,
        } as State;

        const wrapper = await getWrapper(state);

        expect(wrapper.find("[data-test=tracker-creation-xml-file-selector]").exists()).toBe(false);
        expect(wrapper.find("[data-test=injected-file-input]").exists()).toBe(true);
        expect(wrapper.find(".tracker-creation-xml-file-error").exists()).toBe(true);
    });
});
