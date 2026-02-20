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

import { nextTick, ref } from "vue";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import StepDefinitionEditableStep from "./StepDefinitionEditableStep.vue";
import { getGlobalTestOptions } from "./helpers/global-options-for-test";
import * as tuleap_api from "./api/rest-querier";
import { TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML } from "@tuleap/plugin-tracker-constants";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import {
    PROJECT_ID,
    FIELD_ID,
    UPLOAD_URL,
    UPLOAD_FIELD_NAME,
    UPLOAD_MAX_SIZE,
    IS_DRAGGING,
} from "./injection-keys";
import type { Step } from "./Step";

type MockedContent = {
    getContent(): string;
};

type MockedRichTextEditor = {
    createRichTextEditor(): MockedContent;
};

jest.mock("@tuleap/plugin-tracker-rich-text-editor", () => {
    return {
        RichTextEditorFactory: {
            forFlamingParrotWithExistingFormatSelector: (): MockedRichTextEditor => ({
                createRichTextEditor: (): MockedContent => ({
                    getContent: (): string => "some fabulous content",
                }),
            }),
        },
    };
});

const project_id = 102;

function getComponentInstance(
    description_format: TextFieldFormat = TEXT_FORMAT_COMMONMARK,
): VueWrapper<InstanceType<typeof StepDefinitionEditableStep>> {
    return shallowMount(StepDefinitionEditableStep, {
        global: {
            ...getGlobalTestOptions(),
            directives: {
                "dompurify-html": jest.fn(),
            },
            provide: {
                [PROJECT_ID.valueOf()]: project_id,
                [FIELD_ID.valueOf()]: 18,
                [UPLOAD_URL.valueOf()]: "",
                [UPLOAD_FIELD_NAME.valueOf()]: "",
                [UPLOAD_MAX_SIZE.valueOf()]: "",
                [IS_DRAGGING.valueOf()]: ref(false),
            },
        },
        propsData: {
            step: {
                raw_description: "raw description",
                raw_expected_results: "raw expected results",
                description_format,
            } as Step,
        },
    });
}

describe("StepDefinitionEditableStep", () => {
    describe(`The display of the textareas, CommonMark preview or error`, () => {
        it(`display form in edit mode, when it is in edit mode without error`, () => {
            const wrapper = getComponentInstance();

            expect(wrapper.find("[data-test=expected-results-textarea]").isVisible()).toBe(true);
            expect(wrapper.find("[data-test=description-textarea]").isVisible()).toBe(true);

            expect(wrapper.find("[data-test=expected-results-preview]").exists()).toBe(false);
            expect(wrapper.find("[data-test=description-preview]").exists()).toBe(false);

            expect(wrapper.find("[data-test=expected-results-error]").exists()).toBe(false);
            expect(wrapper.find("[data-test=description-error]").exists()).toBe(false);
        });

        it(`display form in preview mode, when it is in edit mode without error`, async () => {
            const wrapper = getComponentInstance();
            wrapper.vm.is_in_preview_mode = true;
            wrapper.vm.is_preview_in_error = false;
            await nextTick();

            expect(wrapper.find("[data-test=expected-results-textarea]").isVisible()).toBe(false);
            expect(wrapper.find("[data-test=description-textarea]").isVisible()).toBe(false);

            expect(wrapper.find("[data-test=expected-results-preview]").isVisible()).toBe(true);
            expect(wrapper.find("[data-test=description-preview]").isVisible()).toBe(true);

            expect(wrapper.find("[data-test=expected-results-error]").exists()).toBe(false);
            expect(wrapper.find("[data-test=description-error]").exists()).toBe(false);
        });

        it(`displays an error when the CommonMark cannot be interpreted`, async () => {
            const wrapper = getComponentInstance();
            wrapper.vm.is_in_preview_mode = false;
            wrapper.vm.is_preview_in_error = true;
            await nextTick();

            expect(wrapper.find("[data-test=expected-results-textarea]").isVisible()).toBe(false);
            expect(wrapper.find("[data-test=description-textarea]").isVisible()).toBe(false);

            expect(wrapper.find("[data-test=expected-results-preview]").exists()).toBe(false);
            expect(wrapper.find("[data-test=description-preview]").exists()).toBe(false);

            expect(wrapper.find("[data-test=expected-results-error]").isVisible()).toBe(true);
            expect(wrapper.find("[data-test=description-error]").isVisible()).toBe(true);
        });
    });
    describe("The preview event handling", () => {
        it(`interprets the CommonMark when the user switch to the preview mode`, async () => {
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockResolvedValue("<p>HTML</p>");

            const wrapper = getComponentInstance();

            const promise = wrapper.vm.togglePreview();
            expect(tuleap_api.postInterpretCommonMark).toHaveBeenCalledWith(
                project_id,
                "raw description",
            );
            expect(tuleap_api.postInterpretCommonMark).toHaveBeenCalledWith(
                project_id,
                "raw expected results",
            );
            expect(wrapper.vm.is_preview_loading).toBe(true);

            await promise;
            expect(wrapper.vm.is_in_preview_mode).toBe(true);
            expect(wrapper.vm.is_preview_in_error).toBe(false);
            expect(wrapper.vm.is_preview_loading).toBe(false);
        });

        it(`does not interpret the CommonMark when the user switch to the edit mode`, async () => {
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockResolvedValue("<p>HTML</p>");

            const wrapper = getComponentInstance();
            wrapper.vm.is_in_preview_mode = true;
            await nextTick();

            wrapper.vm.togglePreview();

            expect(tuleap_api.postInterpretCommonMark).not.toHaveBeenCalled();
        });

        it(`cannot interpret the CommonMark because the route failed to interpret the content`, async () => {
            const expected_error_text = new Error("FAIL!");
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockRejectedValue(
                expected_error_text,
            );

            const wrapper = getComponentInstance();
            const promise = wrapper.vm.togglePreview();

            expect(tuleap_api.postInterpretCommonMark).toHaveBeenCalledWith(
                project_id,
                "raw description",
            );
            expect(wrapper.vm.is_preview_loading).toBe(true);

            await promise;
            expect(wrapper.vm.is_in_preview_mode).toBe(true);
            expect(wrapper.vm.is_preview_in_error).toBe(true);
            expect(wrapper.vm.error_text).toBe(expected_error_text);
            expect(wrapper.vm.is_preview_loading).toBe(false);
        });
        describe("Get the content of the RTE editors", () => {
            it("retrieves the content of the both editors if they are set and if the current step is in HTML format", () => {
                const wrapper = getComponentInstance(TEXT_FORMAT_HTML);

                expect(wrapper.vm.raw_description).toContain("raw description");
                expect(wrapper.vm.raw_expected_results).toContain("raw expected results");

                wrapper.vm.getEditorsContent();

                expect(wrapper.vm.raw_description).toContain("some fabulous content");
                expect(wrapper.vm.raw_expected_results).toContain("some fabulous content");
            });

            it("does not retrieve the RTE content if the format is not HTML", () => {
                const wrapper = getComponentInstance({} as TextFieldFormat);

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

            it("does not retrieve the RTE content if one RTE editor is not set", () => {
                const wrapper = getComponentInstance(TEXT_FORMAT_HTML);

                expect(wrapper.vm.$props.step.raw_description).toContain("raw description");
                expect(wrapper.vm.$props.step.raw_expected_results).toContain(
                    "raw expected results",
                );

                wrapper.vm.editors = [];
                wrapper.vm.getEditorsContent();

                expect(wrapper.vm.$props.step.raw_description).toContain("raw description");
                expect(wrapper.vm.$props.step.raw_expected_results).toContain(
                    "raw expected results",
                );
            });
        });
    });
});
