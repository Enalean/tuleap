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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../../helpers/local-vue";
import ModalArchiveSizeWarningModal from "./ModalArchiveSizeWarning.vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";

describe("ModalArchiveSizeWarningModal", () => {
    function getWrapper(): Wrapper<ModalArchiveSizeWarningModal> {
        const state = {
            configuration: { warning_threshold: 1 },
        };
        const store_options = { state };
        const store = createStoreMock(store_options);

        return shallowMount(ModalArchiveSizeWarningModal, {
            localVue,
            propsData: {
                size: 1050000,
                folderHref: "/download/me/here",
                shouldWarnOsxUser: false,
            },
            mocks: { $store: store },
        });
    }

    it("shows itself when it is mounted", () => {
        const wrapper = getWrapper();

        expect(wrapper.classes("tlp-modal-shown")).toBe(true);
    });

    it("displays the size of the folder in MB", () => {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=download-as-zip-folder-size-warning]").html()).toContain(
            "1.05 MB"
        );
    });

    it("Emits an event when it is closed", () => {
        const wrapper = getWrapper();

        wrapper.find("[data-test=close-archive-size-warning]").trigger("click");

        expect(wrapper.emitted("download-folder-as-zip-modal-closed")?.length).toBe(1);
    });

    it("The [Download] button is a link to the archive, the modal is closed when it is clicked", () => {
        const wrapper = getWrapper();
        const confirm_button = wrapper.find(
            "[data-test=confirm-download-archive-button-despite-size-warning]"
        );

        expect(confirm_button.attributes("href")).toBe("/download/me/here");
        confirm_button.trigger("click");

        expect(wrapper.emitted("download-folder-as-zip-modal-closed")?.length).toBe(1);
    });
});
