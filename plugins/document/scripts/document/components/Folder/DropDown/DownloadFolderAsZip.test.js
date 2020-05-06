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

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../../helpers/local-vue.js";
import DownloadFolderAsZip from "./DownloadFolderAsZip.vue";

describe("DownloadFolderAsZip", () => {
    function getWrapper() {
        const state = { project_name: "tuleap-documentation" };
        const store_options = { state };
        const store = createStoreMock(store_options);

        return shallowMount(DownloadFolderAsZip, {
            localVue,
            propsData: {
                item: {
                    id: 10,
                    type: "folder",
                },
            },
            mocks: { $store: store },
        });
    }

    it("Generates the link to the zip archive of the folder", () => {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=download-as-zip-button]").attributes().href).toBe(
            "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip"
        );
    });
});
