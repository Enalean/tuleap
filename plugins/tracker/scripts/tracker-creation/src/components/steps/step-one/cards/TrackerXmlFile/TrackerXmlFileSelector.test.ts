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
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type Vue from "vue";

describe("TrackerXmlFileSelector", () => {
    async function getWrapper(
        selected_xml_file_input: HTMLInputElement | null,
        has_xml_file_error: boolean,
    ): Promise<Wrapper<Vue>> {
        const wrapper = shallowMount(TrackerXmlFileSelector, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        has_xml_file_error,
                        selected_xml_file_input,
                    },
                }),
            },
            localVue: await createTrackerCreationLocalVue(),
        });

        jest.spyOn(wrapper.vm.$el, "appendChild");

        return wrapper;
    }

    it("renders a fresh input[type=file] when no selection has been made before", async () => {
        const wrapper = await getWrapper(null, false);

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

        const wrapper = await getWrapper(file_input, false);

        expect(wrapper.find("[data-test=tracker-creation-xml-file-selector]").exists()).toBe(false);
        expect(wrapper.find("[data-test=injected-file-input]").exists()).toBe(true);
    });

    it("Displays an error when the provided file is not parsable", async () => {
        const local_document = document.implementation.createHTMLDocument();
        const file_input = local_document.createElement("input");

        file_input.setAttribute("type", "file");
        file_input.setAttribute("data-test", "injected-file-input");

        const wrapper = await getWrapper(file_input, true);

        expect(wrapper.find("[data-test=tracker-creation-xml-file-selector]").exists()).toBe(false);
        expect(wrapper.find("[data-test=injected-file-input]").exists()).toBe(true);
        expect(wrapper.find(".tracker-creation-xml-file-error").exists()).toBe(true);
    });
});
