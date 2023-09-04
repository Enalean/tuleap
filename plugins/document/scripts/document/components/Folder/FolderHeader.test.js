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
jest.mock("@tuleap/tlp-modal", () => {
    return {
        createModal: () => ({
            addEventListener: jest.fn(),
            show: jest.fn(),
        }),
    };
});

import { shallowMount } from "@vue/test-utils";
import FolderHeader from "./FolderHeader.vue";
import { TYPE_EMPTY, TYPE_LINK } from "../../constants";
import emitter from "../../helpers/emitter";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { nextTick } from "vue";

describe("FolderHeader", () => {
    function factory(is_loading_ascendant_hierarchy, is_folder_empty) {
        const dynamic_import_stubs = [
            "confirm-deletion-modal",
            "permissions-update-modal",
            "create-new-item-version-modal",
            "download-folder-size-threshold-exceeded-modal",
            "download-folder-size-warning-modal",
            "file-changelog-modal",
            "update-properties-modal",
        ];

        return shallowMount(FolderHeader, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        is_loading_ascendant_hierarchy,
                        current_folder: { id: 20 },
                    },
                    getters: {
                        current_folder_title: () => "My folder title",
                        is_folder_empty: () => is_folder_empty,
                    },
                    modules: {
                        configuration: {
                            namespaced: true,
                            state: { is_status_property_used: true },
                        },
                    },
                }),
                stubs: dynamic_import_stubs,
            },
        });
    }

    describe("Component rendering -", () => {
        it(`Does not display title information when folder is loading_ascendent_hierarchy`, () => {
            const wrapper = factory(true, true);
            expect(wrapper.get("[data-test=document-folder-header-title]").classes()).toContain(
                "document-folder-title-loading",
            );
        });

        it(`Display title information when folder is loaded`, () => {
            const wrapper = factory(false, true);
            expect(wrapper.get("[data-test=document-folder-header-title]").classes()).toStrictEqual(
                [],
            );
        });
    });
    describe("Search box -", () => {
        it(`Does not display search box, when current folder has no content`, () => {
            const wrapper = factory(false, true);
            expect(
                wrapper.find("[data-test=document-folder-harder-search-box]").exists(),
            ).toBeFalsy();
        });
        it(`Display search box, when folder has content`, () => {
            const wrapper = factory(false, false);
            expect(
                wrapper.find("[data-test=document-folder-harder-search-box]").exists(),
            ).toBeTruthy();
        });
    });

    describe("Modal loading -", () => {
        it(`Loads new item version modal`, async () => {
            const wrapper = factory(false, true);

            const event = { detail: { current_item: { type: TYPE_LINK } } };
            wrapper.vm.showCreateNewItemVersionModal(event);
            await nextTick();
            expect(wrapper.find("[data-test=document-new-version-modal]").exists()).toBe(true);
        });

        it(`Loads new empty version modal`, async () => {
            const wrapper = factory(false, true);

            const event = { item: { type: TYPE_EMPTY }, type: TYPE_LINK };
            wrapper.vm.showCreateNewVersionModalForEmpty(event);
            await nextTick();
            expect(wrapper.find("[data-test=document-new-version-modal]").exists()).toBe(true);
        });

        it(`Loads delete modal`, async () => {
            const wrapper = factory(false, true);
            expect(wrapper.find("[data-test=document-delete-item-modal]").exists()).toBe(false);

            emitter.emit("deleteItem", {
                item: { id: 20 },
            });

            await nextTick();
            expect(wrapper.find("[data-test=document-delete-item-modal]").exists()).toBe(true);
        });

        it(`Loads update properties modal`, async () => {
            const wrapper = factory(false, true);
            const event = { detail: { current_item: { type: TYPE_EMPTY, status: "" } } };
            wrapper.vm.showUpdateItemPropertiesModal(event);
            await nextTick();
            expect(wrapper.find("[data-test=document-update-properties-modal]").exists()).toBe(
                true,
            );
        });

        it(`Loads permission modal`, async () => {
            const wrapper = factory(false, true);
            expect(wrapper.find("[data-test=document-permissions-item-modal]").exists()).toBe(
                false,
            );
            const event = { detail: { current_item: { type: TYPE_EMPTY, properties: [] } } };
            wrapper.vm.showUpdateItemPermissionsModal(event);
            await nextTick();
            expect(wrapper.find("[data-test=document-permissions-item-modal]").exists()).toBe(true);
        });

        it("Loads the folder size threshold exceeded error modal", async () => {
            const wrapper = factory(false, true);
            expect(
                wrapper.find("[data-test=document-folder-size-threshold-exceeded]").exists(),
            ).toBe(false);

            const event = { detail: { current_folder_size: 100000 } };
            wrapper.vm.showMaxArchiveSizeThresholdExceededErrorModal(event);
            await nextTick();
            expect(
                wrapper.find("[data-test=document-folder-size-threshold-exceeded]").exists(),
            ).toBe(true);
        });

        it("Loads the folder size warning modal", async () => {
            const wrapper = factory(false, true);
            expect(wrapper.find("[data-test=document-folder-size-warning-modal]").exists()).toBe(
                false,
            );

            const event = {
                detail: { current_folder_size: 100000, folder_href: "/download/folder/here" },
            };
            wrapper.vm.showArchiveSizeWarningModal(event);
            await nextTick();
            expect(wrapper.find("[data-test=document-folder-size-warning-modal]").exists()).toBe(
                true,
            );
        });

        it("loads the file changelog modal", async () => {
            const wrapper = factory(false, true);
            expect(wrapper.find("[data-test=file-changelog-modal]").exists()).toBe(false);

            const event = {
                detail: { updated_file: { id: 12 }, dropped_file: new Blob() },
            };
            wrapper.vm.showChangelogModal(event);
            await nextTick();
            expect(wrapper.find("[data-test=file-changelog-modal]").exists()).toBe(true);
        });
    });
});
