/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import localVue from "../../../helpers/local-vue";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";
import CurrentFolderDropZone from "./CurrentFolderDropZone.vue";

describe("CurrentFolderDropZone", () => {
    let current_folder_drop_zone_factory, store;
    beforeEach(() => {
        const state = {
            max_files_dragndrop: 10,
            max_size_upload: 10000,
        };

        const store_options = {
            state,
        };

        store = createStoreMock(store_options);

        store.getters.current_folder_title = "My folder";

        current_folder_drop_zone_factory = (props = {}) => {
            return shallowMount(CurrentFolderDropZone, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    describe("Error messages", () => {
        it(`Given user has write permission
            When we display the drop zone
            Then user should have a success message`, () => {
            const wrapper = current_folder_drop_zone_factory({
                user_can_dragndrop_in_current_folder: true,
            });

            expect(
                wrapper.contains("[data-test=document-current-folder-success-dropzone]")
            ).toBeTruthy();
            expect(
                wrapper.contains("[data-test=document-current-folder-error-dropzone]")
            ).toBeFalsy();
        });

        it(`Given user is document reader
            When we display the drop zone
            Then user should have an error message`, () => {
            const wrapper = current_folder_drop_zone_factory({
                user_can_dragndrop_in_current_folder: false,
            });

            expect(
                wrapper.contains("[data-test=document-current-folder-success-dropzone]")
            ).toBeFalsy();
            expect(
                wrapper.contains("[data-test=document-current-folder-error-dropzone]")
            ).toBeTruthy();
        });
    });

    describe("Highlighted classes", () => {
        it(`Given drop zone is highlighted and user can write
                When we display the drop zone
                Then the highlighted zone has success class`, () => {
            const wrapper = current_folder_drop_zone_factory({
                is_dropzone_highlighted: true,
                user_can_dragndrop_in_current_folder: true,
            });

            const current_folder_drop_zone = wrapper.get(
                "[data-test=document-current-folder-dropzone]"
            );
            expect(current_folder_drop_zone.classes()).toContain("shown-success");
        });

        it(`Given drop zone is highlighted and user can read
                When we display the drop zone
                Then the highlighted zone has error class`, () => {
            const wrapper = current_folder_drop_zone_factory({
                is_dropzone_highlighted: true,
                user_can_dragndrop_in_current_folder: false,
            });

            const current_folder_drop_zone = wrapper.get(
                "[data-test=document-current-folder-dropzone]"
            );
            expect(current_folder_drop_zone.classes()).toContain("shown-error");
        });

        it(`Given drop zone is NOT highlighted and user can write
                When we display the drop zone
                Then the highlighted zone has no specific class`, () => {
            const wrapper = current_folder_drop_zone_factory({
                is_dropzone_highlighted: false,
                user_can_dragndrop_in_current_folder: true,
            });

            const current_folder_drop_zone = wrapper.get(
                "[data-test=document-current-folder-dropzone]"
            );
            expect(current_folder_drop_zone.classes()).toEqual([
                "document-upload-to-current-folder",
            ]);
        });

        it(`Given drop zone is NOT highlighted and user can read
                When we display the drop zone
                Then the highlighted zone has no specific class`, () => {
            const wrapper = current_folder_drop_zone_factory({
                is_dropzone_highlighted: false,
                user_can_dragndrop_in_current_folder: false,
            });

            const current_folder_drop_zone = wrapper.get(
                "[data-test=document-current-folder-dropzone]"
            );
            expect(current_folder_drop_zone.classes()).toEqual([
                "document-upload-to-current-folder",
            ]);
        });
    });
});
