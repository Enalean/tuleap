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
import FollowupEditor from "./FollowupEditor.vue";
import { setCatalog } from "../gettext-catalog";
import RichTextEditor from "../common/RichTextEditor.vue";

function getInstance(props = {}, data = {}) {
    return shallowMount(FollowupEditor, {
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

describe(`FollowupEditor`, () => {
    let value;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        value = {
            format: "text",
            body: "",
        };
    });

    it(`when the content changes, it will emit the "input" event with the new content`, () => {
        const wrapper = getInstance({ value });
        wrapper.vm.content = "chrysopid";

        expect(wrapper.emitted("input")[0]).toEqual([
            {
                format: "text",
                body: "chrysopid",
            },
        ]);
    });

    it(`when the RichTextEditor emits a "format-change" event,
        it will emit the "input" event with the new format and the new content`, () => {
        const wrapper = getInstance({ value });
        wrapper.vm.onFormatChange("commonmark", "chrysopid");

        expect(wrapper.emitted("input")[0]).toEqual([
            {
                format: "commonmark",
                body: "chrysopid",
            },
        ]);
    });
    describe("Component display", () => {
        it("shows the Rich Text Editor if there is no error and if the user is in edit mode", () => {
            const is_in_error = false;
            const is_in_preview_mode = false;
            const wrapper = getInstance({ value }, { is_in_error, is_in_preview_mode });

            expect(wrapper.findComponent(RichTextEditor).isVisible()).toBe(true);
            expect(wrapper.find("[data-test=text-field-commonmark-preview]").exists()).toBe(false);
            expect(wrapper.find("[data-test=text-field-error]").exists()).toBe(false);
        });

        it("shows the CommonMark preview if there is no error and if the user is in preview mode", () => {
            const is_in_error = false;
            const is_in_preview_mode = true;
            const wrapper = getInstance({ value }, { is_in_error, is_in_preview_mode });

            expect(wrapper.findComponent(RichTextEditor).isVisible()).toBe(false);
            expect(wrapper.find("[data-test=text-field-commonmark-preview]").exists()).toBe(true);
            expect(wrapper.find("[data-test=text-field-error]").exists()).toBe(false);
        });
        it("shows the error message if there was a problem during the CommonMark interpretation", () => {
            const is_in_error = true;
            const error_text = "Interpretation failed !!!!!!!!";
            const is_in_preview_mode = false;
            const wrapper = getInstance({ value }, { is_in_error, error_text, is_in_preview_mode });

            expect(wrapper.findComponent(RichTextEditor).isVisible()).toBe(false);
            expect(wrapper.find("[data-test=text-field-commonmark-preview]").exists()).toBe(false);
            expect(wrapper.find("[data-test=text-field-error]").exists()).toBe(true);
            expect(wrapper.find("[data-test=text-field-error]").text()).toEqual(
                expect.stringContaining("Interpretation failed !!!!!!!!")
            );
        });
    });
});
