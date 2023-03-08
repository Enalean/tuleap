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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue";
import ClipboardContentInformation from "./ClipboardContentInformation.vue";
import { CLIPBOARD_OPERATION_CUT, CLIPBOARD_OPERATION_COPY } from "../../../constants";
import { useClipboardStore } from "../../../stores/clipboard";
import type { ClipboardState } from "../../../stores/types";
import type { TestingPinia } from "@pinia/testing";
import { createTestingPinia } from "@pinia/testing";

let pinia: TestingPinia;

function getWrapper(clipboard: ClipboardState): Wrapper<ClipboardContentInformation> {
    pinia = createTestingPinia({
        initialState: {
            clipboard,
        },
    });
    useClipboardStore(pinia);

    return shallowMount(ClipboardContentInformation, {
        localVue,
        pinia,
    });
}

describe("ClipboardContentInformation", () => {
    it(`Given there is no item in the clipboard
        Then no information is displayed`, () => {
        const wrapper = getWrapper({
            item_id: null,
            item_type: null,
            item_title: null,
            operation_type: null,
            pasting_in_progress: false,
        });

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given there is an item in the clipboard
        Then information is displayed`, async () => {
        const wrapper = getWrapper({
            item_id: 123,
            item_type: "folder",
            item_title: "My item",
            operation_type: CLIPBOARD_OPERATION_COPY,
            pasting_in_progress: false,
        });

        const result_copy = wrapper.html();
        expect(result_copy).toBeTruthy();

        pinia.state.value.clipboard.operation_type = CLIPBOARD_OPERATION_CUT;
        await wrapper.vm.$nextTick();
        const result_cut = wrapper.html();
        expect(result_cut).toBeTruthy();
        expect(result_cut).not.toStrictEqual(result_copy);

        pinia.state.value.clipboard.operation_type = CLIPBOARD_OPERATION_COPY;
        pinia.state.value.clipboard.pasting_in_progress = true;
        await wrapper.vm.$nextTick();
        const result_copy_paste = wrapper.html();
        expect(result_copy_paste).toBeTruthy();
        expect(result_copy_paste).not.toStrictEqual(result_copy);

        pinia.state.value.clipboard.operation_type = CLIPBOARD_OPERATION_CUT;
        await wrapper.vm.$nextTick();
        const result_cut_paste = wrapper.html();
        expect(result_cut_paste).toBeTruthy();
        expect(result_cut_paste).not.toStrictEqual(result_cut);
        expect(result_cut_paste).not.toStrictEqual(result_copy_paste);
    });
});
