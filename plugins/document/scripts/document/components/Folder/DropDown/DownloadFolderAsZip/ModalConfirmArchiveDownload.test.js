/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../../helpers/local-vue.js";
import ModalConfirmArchiveDownload from "./ModalConfirmArchiveDownload.vue";

describe("ModalConfirmArchiveDownload", () => {
    function getWrapper() {
        const state = { project_name: "tuleap-documentation", warning_threshold: 1 };
        const store_options = { state };
        const store = createStoreMock(store_options);

        return shallowMount(ModalConfirmArchiveDownload, {
            localVue,
            propsData: {
                folderHref: "/download/me/here",
            },
            mocks: { $store: store },
        });
    }

    it("shows itself when it is mounted", () => {
        const wrapper = getWrapper();

        expect(wrapper.classes("tlp-modal-shown")).toBe(true);
    });

    it("Emits an event when it is closed", () => {
        const wrapper = getWrapper();

        wrapper.find("[data-test=close-confirm-archive-download-modal]").trigger("click");

        expect(wrapper.emitted("download-folder-as-zip-modal-closed").length).toBe(1);
    });

    it("The [Download] button is a link to the archive, the modal is closed when it is clicked", () => {
        const wrapper = getWrapper();
        const confirm_button = wrapper.find("[data-test=confirm-download-archive-button]");

        expect(confirm_button.attributes("href")).toEqual("/download/me/here");
        confirm_button.trigger("click");

        expect(wrapper.emitted("download-folder-as-zip-modal-closed").length).toBe(1);
    });
});
