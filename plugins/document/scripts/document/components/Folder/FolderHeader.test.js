/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../helpers/local-vue.js";

import FolderHeader from "./FolderHeader.vue";
import { createStoreMock } from "../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { TYPE_EMPTY } from "../../constants.js";

describe("FolderHeader", () => {
    let factory, store;

    beforeEach(() => {
        const general_store = {
            state: {
                is_loading_ascendant_hierarchy: false,
            },
            getters: {
                current_folder_title: "My folder title",
                is_folder_empty: true,
            },
        };

        store = createStoreMock(general_store);

        const dynamic_import_stubs = [
            "confirm-deletion-modal",
            "permissions-update-modal",
            "create-new-item-version-modal",
            "download-folder-size-threshold-exceeded-modal",
            "download-folder-size-warning-modal",
            "file-changelog-modal",
        ];

        factory = (props = {}) => {
            return shallowMount(FolderHeader, {
                localVue,
                mocks: { $store: store },
                propsData: { ...props },
                stubs: dynamic_import_stubs,
            });
        };
    });
    describe("Component rendering -", () => {
        it(`Does not display title information when folder is loading_ascendent_hierarchy`, () => {
            store.state.is_loading_ascendant_hierarchy = true;

            const wrapper = factory();
            expect(wrapper.get("[data-test=document-folder-header-title]").classes()).toContain(
                "document-folder-title-loading"
            );
        });

        it(`Display title information when folder is loaded`, () => {
            store.state.is_loading_ascendant_hierarchy = false;

            const wrapper = factory();
            expect(wrapper.get("[data-test=document-folder-header-title]").classes()).toEqual([]);
        });
    });
    describe("Search box -", () => {
        it(`Does not display search box, when current folder has no content`, () => {
            store.state.is_loading_ascendant_hierarchy = false;
            store.state.current_folder = { id: 20 };
            store.getters.is_folder_empty = true;

            const wrapper = factory();
            expect(
                wrapper.find("[data-test=document-folder-harder-search-box]").exists()
            ).toBeFalsy();
        });
        it(`Display search box, when folder has content`, () => {
            store.state.is_loading_ascendant_hierarchy = false;
            store.state.current_folder = { id: 20 };
            store.getters.is_folder_empty = false;

            const wrapper = factory();
            expect(
                wrapper.find("[data-test=document-folder-harder-search-box]").exists()
            ).toBeTruthy();
        });
    });

    describe("Modal loading -", () => {
        it(`Loads new item version modal`, async () => {
            store.state.is_loading_ascendant_hierarchy = false;
            store.state.current_folder = { id: 20 };

            const wrapper = factory();
            expect(wrapper.find("[data-test=document-new-version-modal]").exists()).toBe(false);

            const event = { detail: { current_item: { type: TYPE_EMPTY } } };
            wrapper.vm.showCreateNewItemVersionModal(event);
            await wrapper.vm.$nextTick();
            await wrapper.vm.shown_new_version_modal();
            expect(wrapper.find("[data-test=document-new-version-modal]").exists()).toBe(true);
        });

        it(`Loads delete modal`, async () => {
            store.state.is_loading_ascendant_hierarchy = false;
            store.state.current_folder = { id: 20 };

            const wrapper = factory();
            expect(wrapper.find("[data-test=document-delete-item-modal]").exists()).toBe(false);

            const event = { detail: { current_item: { type: TYPE_EMPTY } } };
            wrapper.vm.showDeleteItemModal(event);
            await wrapper.vm.$nextTick();
            expect(wrapper.find("[data-test=document-delete-item-modal]").exists()).toBe(true);
        });

        it(`Loads update metadata modal`, async () => {
            store.state.is_loading_ascendant_hierarchy = false;
            store.state.current_folder = { id: 20 };

            const wrapper = factory();
            expect(wrapper.find("[data-test=document-update-metadata-modal]").exists()).toBe(false);

            const event = { detail: { current_item: { type: TYPE_EMPTY } } };
            wrapper.vm.showUpdateItemMetadataModal(event);
            await wrapper.vm.$nextTick();
            await wrapper.vm.shown_update_metadata_modal();
            expect(wrapper.find("[data-test=document-update-metadata-modal]").exists()).toBe(true);
        });

        it(`Loads permission modal`, async () => {
            store.state.is_loading_ascendant_hierarchy = false;
            store.state.current_folder = { id: 20 };

            const wrapper = factory();
            expect(wrapper.find("[data-test=document-permissions-item-modal]").exists()).toBe(
                false
            );
            const event = { detail: { current_item: { type: TYPE_EMPTY } } };
            wrapper.vm.showUpdateItemPermissionsModal(event);
            await wrapper.vm.$nextTick();
            expect(wrapper.find("[data-test=document-permissions-item-modal]").exists()).toBe(true);
        });

        it("Loads the folder size threshold exceeded error modal", async () => {
            store.state.is_loading_ascendant_hierarchy = false;
            store.state.current_folder = { id: 20 };

            const wrapper = factory();
            expect(
                wrapper.find("[data-test=document-folder-size-threshold-exceeded]").exists()
            ).toBe(false);

            const event = { detail: { current_folder_size: 100000 } };
            wrapper.vm.showMaxArchiveSizeThresholdExceededErrorModal(event);
            await wrapper.vm.$nextTick();
            expect(
                wrapper.find("[data-test=document-folder-size-threshold-exceeded]").exists()
            ).toBe(true);
        });

        it("Loads the folder size warning modal", async () => {
            store.state.is_loading_ascendant_hierarchy = false;
            store.state.current_folder = { id: 20 };

            const wrapper = factory();
            expect(wrapper.find("[data-test=document-folder-size-warning-modal]").exists()).toBe(
                false
            );

            const event = {
                detail: { current_folder_size: 100000, folder_href: "/download/folder/here" },
            };
            wrapper.vm.showArchiveSizeWarningModal(event);
            await wrapper.vm.$nextTick();
            expect(wrapper.find("[data-test=document-folder-size-warning-modal]").exists()).toBe(
                true
            );
        });

        it("loads the file changelog modal", async () => {
            store.state.is_loading_ascendant_hierarchy = false;
            store.state.current_folder = { id: 20 };

            const wrapper = factory();
            expect(wrapper.find("[data-test=file-changelog-modal]").exists()).toBe(false);

            const event = {
                detail: { updated_file: { id: 12 }, dropped_file: new Blob() },
            };
            wrapper.vm.showChangelogModal(event);
            await wrapper.vm.$nextTick();
            expect(wrapper.find("[data-test=file-changelog-modal]").exists()).toBe(true);
        });
    });
});
