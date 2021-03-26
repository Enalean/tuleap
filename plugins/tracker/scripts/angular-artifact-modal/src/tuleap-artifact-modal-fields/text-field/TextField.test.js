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

import localVue from "../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import TextField from "./TextField.vue";
import FormatSelector from "../../common/FormatSelector.vue";
import RichTextEditor from "../../common/RichTextEditor.vue";
import * as disabled_field_detector from "../disabled-field-detector.js";
import * as tuleap_api from "../../api/tuleap-api";
import { setCatalog } from "../../gettext-catalog.js";

let isDisabled;

function getInstance(props = {}, data = {}) {
    return shallowMount(TextField, {
        localVue,
        propsData: {
            ...props,
        },
        data() {
            return {
                ...data,
            };
        },
    });
}

describe(`TextField`, () => {
    let field, value;
    beforeEach(() => {
        isDisabled = jest.spyOn(disabled_field_detector, "isDisabled").mockReturnValue(false);
        field = { field_id: 105, required: true };
        value = {
            format: "commonmark",
            content: "",
        };
    });

    it(`will set the "error" class when the field is required
        and the content is an empty string`, () => {
        const field = { field_id: 105, required: true };
        const value = { format: "commonmark", content: "" };
        const wrapper = getInstance({ field, value });

        expect(wrapper.classes("tlp-form-element-error")).toBe(true);
    });

    it(`will set the "disabled" class`, () => {
        isDisabled.mockReturnValue(true);
        const wrapper = getInstance({ field, value });

        expect(wrapper.classes("tlp-form-element-disabled")).toBe(true);
    });

    it(`when the content changes, it will emit the "input" event with the new content`, () => {
        const wrapper = getInstance({ field, value });
        wrapper.vm.content = "caramba";

        expect(wrapper.emitted("input")[0]).toEqual([
            {
                format: "commonmark",
                content: "caramba",
            },
        ]);
    });

    it(`when the RichTextEditor emits a "format-change" event,
        it will emit the "input" event with the new format and the new content`, () => {
        const wrapper = getInstance({ field, value });
        wrapper.vm.onFormatChange("commonmark", "caramba");

        expect(wrapper.emitted("input")[0]).toEqual([
            {
                format: "commonmark",
                content: "caramba",
            },
        ]);
    });

    it(`will set the same "id" prop to the label and the editor`, () => {
        const field = { field_id: 197, required: true };
        const wrapper = getInstance({ field, value });

        const label = wrapper.findComponent(FormatSelector);
        const editor = wrapper.findComponent(RichTextEditor);

        expect(label.props("id")).toEqual("tracker_field_197");
        expect(editor.props("id")).toEqual("tracker_field_197");
    });
    describe("togglePreview()", () => {
        it("does not interpret the CommonMark when user change to the edit mode", () => {
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockResolvedValue("<p>HTML</p>");

            const is_in_preview_mode = true;
            const wrapper = getInstance({ field, value }, { is_in_preview_mode });

            wrapper.vm.togglePreview();

            expect(tuleap_api.postInterpretCommonMark).not.toHaveBeenCalled();
        });

        it("inteprets the CommonMark", async () => {
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockResolvedValue("<p>HTML</p>");

            const is_in_preview_mode = false;
            const wrapper = getInstance({ field, value }, { is_in_preview_mode });

            wrapper.vm.togglePreview();

            expect(wrapper.vm.$data.is_preview_loading).toBe(true);
            expect(tuleap_api.postInterpretCommonMark).toHaveBeenCalled();

            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.$data.is_in_error).toBe(false);
            expect(wrapper.vm.$data.error_text).toBe("");
            expect(wrapper.vm.$data.is_preview_loading).toBe(false);
            expect(wrapper.vm.$data.is_in_preview_mode).toBe(true);
            expect(wrapper.vm.$data.interpreted_commonmark).toBe("<p>HTML</p>");
        });

        it("displays error if the CommonMark cannot be interpreted", async () => {
            setCatalog({ getString: () => "" });
            const error_text = new Error("Fail to interpret the CommonMark");
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockRejectedValue(error_text);

            const is_in_preview_mode = false;
            const wrapper = getInstance({ field, value }, { is_in_preview_mode });

            wrapper.vm.togglePreview();

            expect(wrapper.vm.$data.is_preview_loading).toBe(true);
            expect(tuleap_api.postInterpretCommonMark).toHaveBeenCalled();

            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.$data.is_in_error).toBe(true);
            expect(wrapper.vm.$data.error_text).toBe(error_text);
            expect(wrapper.vm.$data.is_preview_loading).toBe(false);
            expect(wrapper.vm.$data.is_in_preview_mode).toBe(true);
            expect(wrapper.vm.$data.interpreted_commonmark).toBe("");
        });
    });
    describe("Component display", () => {
        it("shows the Rich Text Editor if there is no error and if the user is in edit mode", () => {
            const is_in_error = false;
            const is_in_preview_mode = false;
            const wrapper = getInstance({ field, value }, { is_in_error, is_in_preview_mode });

            expect(wrapper.findComponent(RichTextEditor).isVisible()).toBe(true);
            expect(wrapper.find("[data-test=text-field-commonmark-preview]").exists()).toBe(false);
            expect(wrapper.find("[data-test=text-field-error]").exists()).toBe(false);
        });

        it("shows the CommonMark preview if there is no error and if the user is in preview mode", () => {
            const is_in_error = false;
            const is_in_preview_mode = true;
            const wrapper = getInstance({ field, value }, { is_in_error, is_in_preview_mode });

            expect(wrapper.findComponent(RichTextEditor).isVisible()).toBe(false);
            expect(wrapper.find("[data-test=text-field-commonmark-preview]").exists()).toBe(true);
            expect(wrapper.find("[data-test=text-field-error]").exists()).toBe(false);
        });
        it("shows the error message if there was a problem during the CommonMark interpretation", () => {
            setCatalog({ getString: () => "There was an error in the Markdown preview:" });
            const is_in_error = true;
            const error_text = "Interpretation failed !!!!!!!!";
            const is_in_preview_mode = false;
            const wrapper = getInstance(
                { field, value },
                { is_in_error, error_text, is_in_preview_mode }
            );

            expect(wrapper.findComponent(RichTextEditor).isVisible()).toBe(false);
            expect(wrapper.find("[data-test=text-field-commonmark-preview]").exists()).toBe(false);
            expect(wrapper.find("[data-test=text-field-error]").exists()).toBe(true);
            expect(wrapper.find("[data-test=text-field-error]").text()).toBe(
                "There was an error in the Markdown preview: Interpretation failed !!!!!!!!"
            );
        });
    });
});
