/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import localVue from "../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import FormatSelector from "./FormatSelector.vue";
import CommonmarkSyntaxHelper from "./CommonmarkSyntaxHelper.vue";
import { setCatalog } from "../gettext-catalog";
import CommonmarkPreviewButton from "./CommonmarkPreviewButton.vue";

function getInstance(props = {}) {
    return shallowMount(FormatSelector, {
        localVue,
        propsData: {
            id: "unique-id",
            label: "My translated label",
            ...props,
        },
    });
}

describe(`FormatSelector`, () => {
    beforeEach(() => {
        setCatalog({ getString: () => "" });
    });

    describe(`when the format was "html"`, () => {
        it(`and when I switch to "text",
            it will dispatch an "input" event with the new format`, () => {
            const wrapper = getInstance({ value: "html" });
            wrapper.vm.format = "text";

            expect(wrapper.emitted("input")[0]).toEqual(["text"]);
        });
    });

    describe(`when the format was "text"`, () => {
        it(`and when I switch to "html",
            it will dispatch an "input" event with the new format`, () => {
            const wrapper = getInstance();
            wrapper.vm.format = "html";

            expect(wrapper.emitted("input")[0]).toEqual(["html"]);
        });
    });

    it(`when the format is anything else, it throws`, () => {
        const wrapper = getInstance();
        expect(wrapper.vm.$options.props.value.validator("markdown")).toBe(false);
    });

    describe(`disabled`, () => {
        it.each([
            ["the field is disabled", true, false, false],
            ["the user is in preview mode", true, false, false],
            ["preview is loading", false, false, true],
        ])(
            "will disable the format selectbox when %s",
            (result_condition, disabled, is_in_preview_mode, is_preview_loading) => {
                const wrapper = getInstance({ disabled, is_in_preview_mode, is_preview_loading });
                const format_selectbox = wrapper.get("[data-test=format]");

                expect(format_selectbox.attributes("disabled")).toBe("disabled");
            }
        );
        it("enables the button if the field is not disabled, if the user is not in preview mode and if the CommonMark interpretation is not loading", () => {
            const disabled = false;
            const is_in_preview_mode = false;
            const is_preview_loading = false;

            const wrapper = getInstance({ disabled, is_in_preview_mode, is_preview_loading });
            const format_selectbox = wrapper.get("[data-test=format]");

            expect(format_selectbox.element.disabled).toBe(false);
        });
    });

    describe(`required`, () => {
        it(`will show a red asterisk icon next to the field label`, () => {
            const required = true;
            const wrapper = getInstance({ required });

            expect(wrapper.find(".fa-asterisk").exists()).toBe(true);
        });
    });
    describe("commonmark syntax helper button and preview button display", () => {
        it.each([["html"], ["text"]])(
            `does not displays the CommonMark related buttons if the chosen format is %s`,
            (format) => {
                const value = format;
                const wrapper = getInstance({ value });
                expect(wrapper.findComponent(CommonmarkSyntaxHelper).exists()).toBeFalsy();
                expect(wrapper.findComponent(CommonmarkSyntaxHelper).exists()).toBeFalsy();
            }
        );
        it(`displays the CommonMark related buttons if the chosen format is 'Markdown'`, () => {
            const value = "commonmark";
            const wrapper = getInstance({ value });
            expect(wrapper.findComponent(CommonmarkSyntaxHelper).exists()).toBeTruthy();
            expect(wrapper.findComponent(CommonmarkPreviewButton).exists()).toBeTruthy();
        });
    });
    describe("disabling of the CommonMark syntax helper button", () => {
        it.each([
            [true, false],
            [false, true],
        ])("disables the syntax helper button", (is_in_preview_mode, is_preview_loading) => {
            setCatalog({ getString: () => "" });
            const wrapper = getInstance({ is_in_preview_mode, is_preview_loading });

            expect(wrapper.vm.is_syntax_helper_button_disabled).toBe(true);
        });
        it("enables the syntax helper button if the preview is not loading and if the user is in edit mode", () => {
            setCatalog({ getString: () => "" });
            const is_in_preview_mode = false;
            const is_preview_loading = false;
            const wrapper = getInstance({ is_in_preview_mode, is_preview_loading });

            expect(wrapper.vm.is_syntax_helper_button_disabled).toBe(false);
        });
    });
});
