/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import SearchItemModals from "./SearchItemModals.vue";
import localVue from "../../helpers/local-vue";
import OngoingUploadModal from "./OngoingUploadModal.vue";
import emitter from "../../helpers/emitter";

describe("SearchItemModals", () => {
    function getWrapper(): Wrapper<SearchItemModals> {
        return shallowMount(SearchItemModals, {
            localVue,
        });
    }

    describe("ongoing-upload-modal", () => {
        it("should not display the ongoing-upload-modal by default", () => {
            const wrapper = getWrapper();

            expect(wrapper.findComponent(OngoingUploadModal).exists()).toBe(false);
        });

        it("should display the ongoing-upload-modal when the user starts an upload", async () => {
            const wrapper = getWrapper();

            emitter.emit("item-is-being-uploaded");
            await wrapper.vm.$nextTick();

            expect(wrapper.findComponent(OngoingUploadModal).exists()).toBe(true);
        });

        it("should display again the ongoing-upload-modal if it has been closed but user starts another upload", async () => {
            const wrapper = getWrapper();

            emitter.emit("item-is-being-uploaded");
            await wrapper.vm.$nextTick();

            const modal = wrapper.findComponent(OngoingUploadModal);
            expect(modal.exists()).toBe(true);
            modal.vm.$emit("close");
            await wrapper.vm.$nextTick();

            expect(wrapper.findComponent(OngoingUploadModal).exists()).toBe(false);

            emitter.emit("item-is-being-uploaded");
            await wrapper.vm.$nextTick();

            expect(wrapper.findComponent(OngoingUploadModal).exists()).toBe(true);
        });
    });
});
