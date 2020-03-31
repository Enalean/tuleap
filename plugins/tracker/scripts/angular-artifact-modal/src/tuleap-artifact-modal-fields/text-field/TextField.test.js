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

const field = { field_id: 105, required: false };
let isDisabled, value;

function getInstance() {
    return shallowMount(TextField, {
        localVue,
        propsData: {
            field,
            value,
        },
    });
}

describe(`TextField`, () => {
    beforeEach(() => {
        isDisabled = jest.spyOn(disabled_field_detector, "isDisabled").mockReturnValue(false);

        value = {
            format: "text",
            content: "",
        };
    });

    it(`will set the "error" class when the field is required
        and the content is an empty string`, () => {
        field.required = true;
        value.content = "";
        const wrapper = getInstance();

        expect(wrapper.classes("tlp-form-element-error")).toBe(true);
    });

    it(`will set the "disabled" class`, () => {
        isDisabled.mockReturnValue(true);
        const wrapper = getInstance();

        expect(wrapper.classes("tlp-form-element-disabled")).toBe(true);
    });

    it(`when the content changes, it will emit the "input" event with the new content`, () => {
        const wrapper = getInstance();
        wrapper.vm.content = "caramba";

        expect(wrapper.emitted("input")[0]).toEqual([
            {
                format: "text",
                content: "caramba",
            },
        ]);
    });

    it(`when the format changes, it will emit the "input" event with the new format`, () => {
        const wrapper = getInstance();
        wrapper.vm.format = "html";

        expect(wrapper.emitted("input")[0]).toEqual([
            {
                format: "html",
                content: "",
            },
        ]);
    });

    it(`will set the same "id" prop to the label and the editor`, () => {
        field.field_id = 197;
        const wrapper = getInstance();

        const label = wrapper.get(FormatSelector);
        const editor = wrapper.get(RichTextEditor);

        expect(label.props("id")).toEqual("tracker_field_197");
        expect(editor.props("id")).toEqual("tracker_field_197");
    });
});
