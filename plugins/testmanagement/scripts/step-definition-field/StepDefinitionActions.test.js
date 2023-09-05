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

import StepDefinitionActions from "./StepDefinitionActions.vue";
import { shallowMount } from "@vue/test-utils";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";
import CommonmarkPreviewButton from "./CommonMark/CommonmarkPreviewButton.vue";
import CommonmarkSyntaxHelper from "./CommonMark/CommonmarkSyntaxHelper.vue";
import localVue from "./helpers/local-vue.js";

let factory;

describe(`StepDefinitionActions`, () => {
    beforeEach(() => {
        factory = (props = {}) => {
            return shallowMount(StepDefinitionActions, {
                localVue,
                propsData: { ...props },
            });
        };
    });
    describe("Display of the CommonMark buttons", () => {
        it(`displays the 'Preview' and the syntax helper buttons when the CommonMark/Markdown value is selected or not disabled`, () => {
            const wrapper = factory({
                value: TEXT_FORMAT_COMMONMARK,
                disabled: false,
            });

            expect(wrapper.findComponent(CommonmarkPreviewButton).exists()).toBe(true);
            expect(wrapper.findComponent(CommonmarkSyntaxHelper).exists()).toBe(true);
        });

        it(`does not display the 'Preview' and the syntax helper buttons when the step is deleted even if the format is CommonMark/Markdown`, () => {
            const wrapper = factory({
                value: TEXT_FORMAT_COMMONMARK,
                disabled: true,
            });

            expect(wrapper.findComponent(CommonmarkPreviewButton).exists()).toBe(false);
            expect(wrapper.findComponent(CommonmarkSyntaxHelper).exists()).toBe(false);
        });

        it.each([[TEXT_FORMAT_HTML], [TEXT_FORMAT_TEXT]])(
            `does not display the buttons when the selected format is %s`,
            (selected_format) => {
                const wrapper = factory({
                    value: selected_format,
                    disabled: false,
                });

                expect(wrapper.findComponent(CommonmarkPreviewButton).exists()).toBe(false);
                expect(wrapper.findComponent(CommonmarkSyntaxHelper).exists()).toBe(false);
            },
        );
    });
    describe(`Selection of the right format`, () => {
        it.each([[TEXT_FORMAT_HTML], [TEXT_FORMAT_TEXT], [TEXT_FORMAT_COMMONMARK]])(
            `selects the '%s' format according the prop 'value' value`,
            (value) => {
                const wrapper = factory({
                    value,
                });
                expect(
                    wrapper.find("[data-test=ttm-definition-step-description-format-" + value + "]")
                        .element.selected,
                ).toBe(true);
            },
        );
    });
    describe("Enabling of the selectbox", () => {
        it(`Enable the selectbox when we are in edit mode AND if the step is not disabled`, () => {
            const wrapper = factory({ disabled: false, is_in_preview_mode: false });

            expect(
                wrapper.find("[data-test=ttm-definition-step-description-format]").element.disabled,
            ).toBe(false);
        });

        it.each([
            [true, false],
            [true, true],
            [false, true],
        ])(
            `Disable the select box when the user is in preview mode OR if the step is disabled`,
            (disabled, is_in_preview_mode) => {
                const wrapper = factory({ disabled, is_in_preview_mode });

                expect(
                    wrapper.find("[data-test=ttm-definition-step-description-format]").element
                        .disabled,
                ).toBe(true);
            },
        );
    });
});
