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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-tests";
import type { State } from "../../../../../store/type";

describe("TrackerXmlFileSelector", () => {
    let mock_set_selected_tracker: jest.Mock;

    beforeEach(() => {
        mock_set_selected_tracker = jest.fn();
    });

    function getWrapper(
        selected_xml_file_input: HTMLInputElement | null,
        has_xml_file_error: boolean,
    ): VueWrapper {
        return shallowMount(TrackerXmlFileSelector, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        has_xml_file_error,
                        selected_xml_file_input,
                    } as State,
                    mutations: {
                        setSelectedTrackerXmlFileInput: mock_set_selected_tracker,
                    },
                }),
            },
        });
    }

    it("renders a fresh input[type=file] when no selection has been made before", () => {
        const wrapper = getWrapper(null, false);

        const file_input = wrapper.find("[data-test=tracker-creation-xml-file-selector]");

        expect(file_input.exists()).toBe(true);

        expect(mock_set_selected_tracker).toHaveBeenCalledWith(
            expect.anything(),
            file_input.element,
        );
    });

    it("renders the stored input when a selection has been made before", async () => {
        const local_document = document.implementation.createHTMLDocument();
        const file_input = local_document.createElement("input");

        file_input.setAttribute("type", "file");
        file_input.setAttribute("data-test", "injected-file-input");

        const wrapper = getWrapper(file_input, false);
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=tracker-creation-xml-file-selector]").exists()).toBe(false);
        expect(wrapper.find("[data-test=injected-file-input]").exists()).toBe(true);
    });

    it("Displays an error when the provided file is not parsable", async () => {
        const local_document = document.implementation.createHTMLDocument();
        const file_input = local_document.createElement("input");

        file_input.setAttribute("type", "file");
        file_input.setAttribute("data-test", "injected-file-input");

        const wrapper = getWrapper(file_input, true);
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=tracker-creation-xml-file-selector]").exists()).toBe(false);
        expect(wrapper.find("[data-test=injected-file-input]").exists()).toBe(true);
        expect(wrapper.find(".tracker-creation-xml-file-error").exists()).toBe(true);
    });
});
