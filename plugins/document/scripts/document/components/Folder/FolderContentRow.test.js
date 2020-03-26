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

import { createStoreMock } from "../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import FolderContentRow from "./FolderContentRow.vue";
import { TYPE_FILE } from "../../constants.js";

function getFolderContentRowInstance(store, props) {
    return shallowMount(FolderContentRow, {
        localVue,
        propsData: props,
        mocks: { $store: store },
    });
}

describe("FolderContentRow", () => {
    let item, store_options, store, wrapper;
    beforeEach(() => {
        item = {
            id: 42,
            title: "my item",
            is_uploading: false,
            is_uploading_new_version: false,
            is_uploading_in_collapsed_folder: false,
            type: TYPE_FILE,
            file_type: "text",
        };

        store_options = {
            state: {
                folded_items_ids: [],
                date_time_format: "YYYY-MM-DD",
            },
        };

        store = createStoreMock(store_options);
    });

    describe("Quick look and dropdown menu rendering", () => {
        it("Should render the quick look button and the dropdown menu when no upload action is in progress", () => {
            wrapper = getFolderContentRowInstance(store, {
                item,
                isQuickLookDisplayed: false,
            });

            expect(wrapper.contains("[data-test=quick-look-button]")).toBeTruthy();
            expect(wrapper.contains("[data-test=dropdown-button]")).toBeTruthy();
            expect(wrapper.contains("[data-test=dropdown-menu]")).toBeTruthy();
        });

        it("Should not render the quick look button and the dropdown menu when the item is being uploaded in a collapsed folder", () => {
            item.is_uploading_in_collapsed_folder = true;

            wrapper = getFolderContentRowInstance(store, {
                item,
                isQuickLookDisplayed: false,
            });

            expect(wrapper.contains("[data-test=quick-look-button]")).toBeFalsy();
            expect(wrapper.contains("[data-test=dropdown-button]")).toBeFalsy();
            expect(wrapper.contains("[data-test=dropdown-menu]")).toBeFalsy();
        });

        it("Should not render the quick look button and the dropdown menu when the item is being uploaded", () => {
            item.is_uploading = true;

            wrapper = getFolderContentRowInstance(store, {
                item,
                isQuickLookDisplayed: false,
            });

            expect(wrapper.contains("[data-test=quick-look-button]")).toBeFalsy();
            expect(wrapper.contains("[data-test=dropdown-button]")).toBeFalsy();
            expect(wrapper.contains("[data-test=dropdown-menu]")).toBeFalsy();
        });

        it("Should not render the quick look button and the dropdown menu when a new version of the item is being uploaded", () => {
            item.is_uploading_new_version = true;

            wrapper = getFolderContentRowInstance(store, {
                item,
                isQuickLookDisplayed: false,
            });

            expect(wrapper.contains("[data-test=quick-look-button]")).toBeFalsy();
            expect(wrapper.contains("[data-test=dropdown-button]")).toBeFalsy();
            expect(wrapper.contains("[data-test=dropdown-menu]")).toBeFalsy();
        });
    });

    describe("Progress bar rendering", () => {
        describe("When the quick look pane is open", () => {
            it("Should render the progress bar when the quick look pane is open and the item is being uploaded in a collapsed folder", () => {
                item.is_uploading_in_collapsed_folder = true;

                wrapper = getFolderContentRowInstance(store, {
                    item,
                    isQuickLookDisplayed: true,
                });

                expect(
                    wrapper.contains("[data-test=progress-bar-quick-look-pane-open]")
                ).toBeTruthy();

                expect(wrapper.contains(".document-tree-cell-owner")).toBeFalsy();
                expect(wrapper.contains(".document-tree-cell-updatedate")).toBeFalsy();
            });

            it("Should render the progress bar when the quick look pane is open and a new version of the item is being uploaded", () => {
                item.is_uploading_new_version = true;

                wrapper = getFolderContentRowInstance(store, {
                    item,
                    isQuickLookDisplayed: true,
                });

                expect(
                    wrapper.contains("[data-test=progress-bar-quick-look-pane-open]")
                ).toBeTruthy();

                expect(wrapper.contains(".document-tree-cell-owner")).toBeFalsy();
                expect(wrapper.contains(".document-tree-cell-updatedate")).toBeFalsy();
            });
        });

        describe("When the quick-look pane is closed", () => {
            it("Should render the progress bar when the quick look pane is closed and the item is being uploaded in a collapsed folder", () => {
                item.is_uploading_in_collapsed_folder = true;

                wrapper = getFolderContentRowInstance(store, {
                    item,
                    isQuickLookDisplayed: false,
                });

                expect(
                    wrapper.contains("[data-test=progress-bar-quick-look-pane-closed]")
                ).toBeTruthy();

                expect(wrapper.contains(".document-tree-cell-owner")).toBeFalsy();
                expect(wrapper.contains(".document-tree-cell-updatedate")).toBeFalsy();
            });
        });

        it("Should render the progress bar when the quick look pane is closed and a new version of the item is being uploaded", () => {
            item.is_uploading_new_version = true;

            wrapper = getFolderContentRowInstance(store, {
                item,
                isQuickLookDisplayed: false,
            });

            expect(
                wrapper.contains("[data-test=progress-bar-quick-look-pane-closed]")
            ).toBeTruthy();

            expect(wrapper.contains(".document-tree-cell-owner")).toBeFalsy();
            expect(wrapper.contains(".document-tree-cell-updatedate")).toBeFalsy();
        });
    });

    describe("User badge and last update date rendering", () => {
        it("Should render the user badge and the last update date only when the quick look pane is closed", () => {
            wrapper = getFolderContentRowInstance(store, {
                item,
                isQuickLookDisplayed: false,
            });

            expect(wrapper.contains(".document-tree-cell-owner")).toBeTruthy();
            expect(wrapper.contains(".document-tree-cell-updatedate")).toBeTruthy();
        });

        it("Should not render the user badge and the last update date when the quick look pane is open", () => {
            wrapper = getFolderContentRowInstance(store, {
                item,
                isQuickLookDisplayed: true,
            });

            expect(wrapper.contains(".document-tree-cell-owner")).toBeFalsy();
            expect(wrapper.contains(".document-tree-cell-updatedate")).toBeFalsy();
        });
    });
});
