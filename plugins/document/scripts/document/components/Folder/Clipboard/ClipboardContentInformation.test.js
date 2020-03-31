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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";
import ClipboardContentInformation from "./ClipboardContentInformation.vue";
import { CLIPBOARD_OPERATION_CUT, CLIPBOARD_OPERATION_COPY } from "../../../constants.js";

describe("ClipboardContentInformation", () => {
    let store, content_information_factory;
    beforeEach(() => {
        store = createStoreMock({}, { clipboard: {} });

        content_information_factory = () => {
            return shallowMount(ClipboardContentInformation, {
                localVue,
                mocks: { $store: store },
            });
        };
    });

    it(`Given there is no item in the clipboard
        Then no information is displayed`, () => {
        store.state.clipboard = { item_title: null };

        const wrapper = content_information_factory();

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given there is an item in the clipboard
        Then information is displayed`, async () => {
        store.state.clipboard = {
            item_title: "My item",
            operation_type: CLIPBOARD_OPERATION_COPY,
            pasting_in_progress: false,
        };

        const wrapper = content_information_factory();

        const result_copy = wrapper.html();
        expect(result_copy).toBeTruthy();

        store.state.clipboard.operation_type = CLIPBOARD_OPERATION_CUT;
        await wrapper.vm.$nextTick();
        const result_cut = wrapper.html();
        expect(result_cut).toBeTruthy();
        expect(result_cut).not.toEqual(result_copy);

        store.state.clipboard.operation_type = CLIPBOARD_OPERATION_COPY;
        store.state.clipboard.pasting_in_progress = true;
        await wrapper.vm.$nextTick();
        const result_copy_paste = wrapper.html();
        expect(result_copy_paste).toBeTruthy();
        expect(result_copy_paste).not.toEqual(result_copy);

        store.state.clipboard.operation_type = CLIPBOARD_OPERATION_CUT;
        await wrapper.vm.$nextTick();
        const result_cut_paste = wrapper.html();
        expect(result_cut_paste).toBeTruthy();
        expect(result_cut_paste).not.toEqual(result_cut);
        expect(result_cut_paste).not.toEqual(result_copy_paste);
    });
});
