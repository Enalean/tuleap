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

import { textfield_mixin } from "./textfield-mixin.js";
import * as tuleap_api from "../api/tuleap-api";
import { setCatalog } from "../gettext-catalog.js";
import { shallowMount } from "@vue/test-utils";
import localVue from "../helpers/local-vue.js";

const ComponentTest = {
    template: "<div>huhuhuhu</div>",
    mixins: [textfield_mixin],
};

function getInstance(data = {}) {
    return shallowMount(ComponentTest, {
        localVue,
        data() {
            return {
                ...data,
            };
        },
    });
}
describe("textfield_mixin", () => {
    describe("interpretCommonMark()", () => {
        it("does not interpret the CommonMark when user change to the edit mode", () => {
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockResolvedValue("<p>HTML</p>");
            const content = "# Oh no! Anyway...";

            const is_in_preview_mode = true;
            const wrapper = getInstance({ is_in_preview_mode });

            wrapper.vm.interpretCommonMark(content);

            expect(tuleap_api.postInterpretCommonMark).not.toHaveBeenCalled();
        });

        it("interprets the CommonMark", async () => {
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockResolvedValue("<p>HTML</p>");
            const content = "# Markdown title";

            const is_in_preview_mode = false;
            const wrapper = getInstance({ is_in_preview_mode });

            wrapper.vm.interpretCommonMark(content);

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
            const content = "# Oh no! Anyway...";

            const is_in_preview_mode = false;
            const wrapper = getInstance({ is_in_preview_mode });

            wrapper.vm.interpretCommonMark(content);

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
});
