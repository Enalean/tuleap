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
import { createLocalVueForTests } from "./helpers/local-vue.js";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import * as tuleap_api from "./api/tuleap-api.js";
import { TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML } from "@tuleap/plugin-tracker-constants";

jest.mock("@tuleap/plugin-tracker-rich-text-editor", () => {
    return {
        RichTextEditorFactory: {
            forFlamingParrotWithExistingFormatSelector: () => ({
                createRichTextEditor: () => ({
                    getContent: () => "some fabulous content",
                }),
            }),
        },
    };
});

async function getComponentInstance(data = {}, description_format = TEXT_FORMAT_COMMONMARK) {
    const state = {
        is_dragging: false,
        field_id: 18,
        project_id: 102,
    };

    const store_options = { state };
    const store = createStoreMock(store_options);

    return shallowMount(StepDefinitionEditableStep, {
        localVue: await createLocalVueForTests(),
        propsData: {
            step: {
                raw_description: "raw description",
                raw_expected_results: "raw expected results",
                description_format,
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
        it(`displays both textareas if the user is in edit mode and if there is no error`, async () => {
            const wrapper = await getComponentInstance({
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

        it(`displays both preview if the user is in preview mode and there is no error during the CommonMark interpretation`, async () => {
            const wrapper = await getComponentInstance({
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

        it(`displays an error when the CommonMark cannot be interpreted`, async () => {
            const wrapper = await getComponentInstance({
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
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockResolvedValue("<p>HTML</p>");

            const wrapper = await getComponentInstance({
                is_in_preview_mode: false,
                is_preview_loading: false,
                is_preview_in_error: false,
            });

            const promise = wrapper.vm.togglePreview();
            expect(tuleap_api.postInterpretCommonMark).toHaveBeenCalledWith("raw description");
            expect(tuleap_api.postInterpretCommonMark).toHaveBeenCalledWith("raw expected results");
            expect(wrapper.vm.$data.is_preview_loading).toBe(true);

            await promise;
            expect(wrapper.vm.$data.is_in_preview_mode).toBe(true);
            expect(wrapper.vm.$data.is_preview_in_error).toBe(false);
            expect(wrapper.vm.$data.is_preview_loading).toBe(false);
        });

        it(`does not interpret the CommonMark when the user switch to the edit mode`, async () => {
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockResolvedValue("<p>HTML</p>");

            const wrapper = await getComponentInstance({
                is_in_preview_mode: true,
            });

            wrapper.vm.togglePreview();

            expect(tuleap_api.postInterpretCommonMark).not.toHaveBeenCalled();
        });

        it(`cannot interpret the CommonMark because the route failed to interpret the content`, async () => {
            const expected_error_text = new Error("FAIL!");
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockRejectedValue(
                expected_error_text,
            );

            const wrapper = await getComponentInstance({
                is_in_preview_mode: false,
                is_preview_loading: false,
                is_preview_in_error: false,
            });
            const promise = wrapper.vm.togglePreview();

            expect(tuleap_api.postInterpretCommonMark).toHaveBeenCalledWith("raw description");
            expect(wrapper.vm.$data.is_preview_loading).toBe(true);

            await promise;
            expect(wrapper.vm.$data.is_in_preview_mode).toBe(true);
            expect(wrapper.vm.$data.is_preview_in_error).toBe(true);
            expect(wrapper.vm.$data.error_text).toBe(expected_error_text);
            expect(wrapper.vm.$data.is_preview_loading).toBe(false);
        });
        describe("Get the content of the RTE editors", () => {
            it("retrieves the content of the both editors if they are set and if the current step is in HTML format", async () => {
                const wrapper = await getComponentInstance({}, TEXT_FORMAT_HTML);

                expect(wrapper.vm.$props.step.raw_description).toContain("raw description");
                expect(wrapper.vm.$props.step.raw_expected_results).toContain(
                    "raw expected results",
                );

                wrapper.vm.getEditorsContent();

                expect(wrapper.vm.$props.step.raw_description).toContain("some fabulous content");
                expect(wrapper.vm.$props.step.raw_expected_results).toContain(
                    "some fabulous content",
                );
            });

            it("does not retrieve the RTE content if the format is not HTML", async () => {
                const wrapper = await getComponentInstance({});

                expect(wrapper.vm.$props.step.raw_description).toContain("raw description");
                expect(wrapper.vm.$props.step.raw_expected_results).toContain(
                    "raw expected results",
                );

                wrapper.vm.getEditorsContent();

                expect(wrapper.vm.$props.step.raw_description).toContain("raw description");
                expect(wrapper.vm.$props.step.raw_expected_results).toContain(
                    "raw expected results",
                );
            });

            it("does not retrieve the RTE content if one RTE editor is not set", async () => {
                const wrapper = await getComponentInstance({}, TEXT_FORMAT_HTML);

                expect(wrapper.vm.$props.step.raw_description).toContain("raw description");
                expect(wrapper.vm.$props.step.raw_expected_results).toContain(
                    "raw expected results",
                );

                wrapper.setData({ editors: [] });
                wrapper.vm.getEditorsContent();

                expect(wrapper.vm.$props.step.raw_description).toContain("raw description");
                expect(wrapper.vm.$props.step.raw_expected_results).toContain(
                    "raw expected results",
                );
            });
        });
    });
});
