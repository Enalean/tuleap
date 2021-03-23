/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import StepDefinitionEditableStep from "./StepDefinitionEditableStep.vue";
import localVue from "./helpers/local-vue.js";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest.js";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import * as tuleap_api from "./api/tuleap-api.js";

let store;
function getComponentInstance(data = {}) {
    const editor_factory = {
        createRichTextEditor: () => {
            return jest.fn();
        },
    };
    jest.spyOn(RichTextEditorFactory, "forFlamingParrotWithExistingFormatSelector").mockReturnValue(
        editor_factory
    );

    const state = {
        is_dragging: false,
        field_id: 18,
        project_id: 102,
    };

    const store_options = { state };
    store = createStoreMock(store_options);

    return shallowMount(StepDefinitionEditableStep, {
        localVue,
        propsData: {
            step: {
                raw_description: "raw description",
                raw_expected_results: "raw expected results",
            },
        },
        data() {
            return {
                ...data,
            };
        },
        mocks: { $store: store },
    });
}

describe("StepDefinitionEditableStep", () => {
    describe(`The display of the textareas, CommonMark preview or error`, () => {
        it(`displays both textareas if the user is in edit mode and if there is no error`, () => {
            const wrapper = getComponentInstance({
                is_in_preview_mode: false,
                is_preview_in_error: false,
            });

            expect(wrapper.find("[data-test=expected-results-textarea").isVisible()).toBe(true);
            expect(wrapper.find("[data-test=description-textarea").isVisible()).toBe(true);

            expect(wrapper.find("[data-test=expected-results-preview").exists()).toBe(false);
            expect(wrapper.find("[data-test=description-preview").exists()).toBe(false);

            expect(wrapper.find("[data-test=expected-results-error").exists()).toBe(false);
            expect(wrapper.find("[data-test=description-error").exists()).toBe(false);
        });

        it(`displays both preview if the user is in preview mode and there is no error during the CommonMark interpretation`, () => {
            const wrapper = getComponentInstance({
                is_in_preview_mode: true,
                is_preview_in_error: false,
            });

            expect(wrapper.find("[data-test=expected-results-textarea").isVisible()).toBe(false);
            expect(wrapper.find("[data-test=description-textarea").isVisible()).toBe(false);

            expect(wrapper.find("[data-test=expected-results-preview").isVisible()).toBe(true);
            expect(wrapper.find("[data-test=description-preview").isVisible()).toBe(true);

            expect(wrapper.find("[data-test=expected-results-error").exists()).toBe(false);
            expect(wrapper.find("[data-test=description-error").exists()).toBe(false);
        });

        it(`displays an error when the CommonMark cannot be interpreted`, () => {
            const wrapper = getComponentInstance({
                is_in_preview_mode: false,
                is_preview_in_error: true,
            });

            expect(wrapper.find("[data-test=expected-results-textarea").isVisible()).toBe(false);
            expect(wrapper.find("[data-test=description-textarea").isVisible()).toBe(false);

            expect(wrapper.find("[data-test=expected-results-preview").exists()).toBe(false);
            expect(wrapper.find("[data-test=description-preview").exists()).toBe(false);

            expect(wrapper.find("[data-test=expected-results-error").isVisible()).toBe(true);
            expect(wrapper.find("[data-test=description-error").isVisible()).toBe(true);
        });
    });
    describe("The preview event handling", () => {
        it(`interprets the CommonMark when the user switch to the preview mode`, async () => {
            jest.spyOn(tuleap_api, "interpretCommonMark").mockResolvedValue("<p>HTML</p>");

            const wrapper = getComponentInstance({
                is_in_preview_mode: false,
                is_preview_loading: false,
                is_preview_in_error: false,
            });

            const promise = wrapper.vm.togglePreview();
            expect(tuleap_api.interpretCommonMark).toHaveBeenCalledWith("raw description");
            expect(tuleap_api.interpretCommonMark).toHaveBeenCalledWith("raw expected results");
            expect(wrapper.vm.$data.is_preview_loading).toBe(true);

            await promise;
            expect(wrapper.vm.$data.is_in_preview_mode).toBe(true);
            expect(wrapper.vm.$data.is_preview_in_error).toBe(false);
            expect(wrapper.vm.$data.is_preview_loading).toBe(false);
        });

        it(`does not interpret the CommonMark when the user switch to the edit mode`, () => {
            jest.spyOn(tuleap_api, "interpretCommonMark").mockResolvedValue("<p>HTML</p>");

            const wrapper = getComponentInstance({
                is_in_preview_mode: true,
            });

            wrapper.vm.togglePreview();

            expect(tuleap_api.interpretCommonMark).not.toHaveBeenCalled();
        });

        it(`cannot interpret the CommonMark because the route failed to interpret the content`, async () => {
            jest.spyOn(tuleap_api, "interpretCommonMark").mockRejectedValue(new Error());

            const wrapper = getComponentInstance({
                is_in_preview_mode: false,
                is_preview_loading: false,
                is_preview_in_error: false,
            });

            const promise = wrapper.vm.togglePreview();

            expect(tuleap_api.interpretCommonMark).toHaveBeenCalledWith("raw description");
            expect(wrapper.vm.$data.is_preview_loading).toBe(true);

            await promise;
            expect(wrapper.vm.$data.is_in_preview_mode).toBe(true);
            expect(wrapper.vm.$data.is_preview_in_error).toBe(true);
            expect(wrapper.vm.$data.is_preview_loading).toBe(false);
        });
    });
});
