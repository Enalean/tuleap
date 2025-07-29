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

import { afterEach, beforeEach, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FolderContentRow from "./FolderContentRow.vue";
import { TYPE_FILE } from "../../constants";
import emitter from "../../helpers/emitter";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

function getFolderContentRowInstance(props): VueWrapper<FolderContentRow> {
    return shallowMount(FolderContentRow, {
        props: {
            is_quick_look_displayed: false,
            ...props,
        },
        global: {
            ...getGlobalTestOptions({
                state: {
                    folded_items_ids: [],
                    configuration: { project_id: 101 },
                    current_folder: {},
                    folder_content: [],
                },
            }),
        },
    });
}

describe("FolderContentRow", () => {
    let item;
    let toggle_quick_look_calls: Array<object>;

    beforeEach(() => {
        item = {
            id: 42,
            title: "my item",
            is_uploading: false,
            is_uploading_new_version: false,
            is_uploading_in_collapsed_folder: false,
            type: TYPE_FILE,
            file_type: "text",
            last_update_date: "2021-06-23",
            owner: {},
        };

        toggle_quick_look_calls = [];
        emitter.on("toggle-quick-look", (event) => toggle_quick_look_calls.push(event));
    });

    afterEach(() => {
        emitter.all.clear();
    });

    describe("Quick look and dropdown menu rendering", () => {
        it("Should render the quick look button and the dropdown menu when no upload action is in progress", () => {
            const wrapper = getFolderContentRowInstance({
                item,
                is_quick_look_displayed: false,
            });

            expect(wrapper.find("[data-test=quick-look-button]").exists()).toBeTruthy();
            expect(wrapper.find("[data-test=dropdown-button]").exists()).toBeTruthy();
        });

        it("Should not render the quick look button and the dropdown menu when the item is being uploaded in a collapsed folder", () => {
            item.is_uploading_in_collapsed_folder = true;

            const wrapper = getFolderContentRowInstance({
                item,
                is_quick_look_displayed: false,
            });

            expect(wrapper.find("[data-test=quick-look-button]").exists()).toBeFalsy();
            expect(wrapper.find("[data-test=dropdown-button]").exists()).toBeFalsy();
            expect(wrapper.find("[data-test=dropdown-menu]").exists()).toBeFalsy();
        });

        it("Should not render the quick look button and the dropdown menu when the item is being uploaded", () => {
            item.is_uploading = true;

            const wrapper = getFolderContentRowInstance({
                item,
                is_quick_look_displayed: false,
            });

            expect(wrapper.find("[data-test=quick-look-button]").exists()).toBeFalsy();
            expect(wrapper.find("[data-test=dropdown-button]").exists()).toBeFalsy();
            expect(wrapper.find("[data-test=dropdown-menu]").exists()).toBeFalsy();
        });

        it("Should not render the quick look button and the dropdown menu when a new version of the item is being uploaded", () => {
            item.is_uploading_new_version = true;

            const wrapper = getFolderContentRowInstance({
                item,
                is_quick_look_displayed: false,
            });

            expect(wrapper.find("[data-test=quick-look-button]").exists()).toBeFalsy();
            expect(wrapper.find("[data-test=dropdown-button]").exists()).toBeFalsy();
            expect(wrapper.find("[data-test=dropdown-menu]").exists()).toBeFalsy();
        });
    });

    describe("Progress bar rendering", () => {
        describe("When the quick look pane is open", () => {
            it("Should render the progress bar when the quick look pane is open and the item is being uploaded in a collapsed folder", () => {
                item.is_uploading_in_collapsed_folder = true;

                const wrapper = getFolderContentRowInstance({
                    item,
                    is_quick_look_displayed: true,
                });

                expect(
                    wrapper.find("[data-test=progress-bar-quick-look-pane-open]").exists(),
                ).toBeTruthy();

                expect(wrapper.find(".document-tree-cell-owner").exists()).toBeFalsy();
                expect(wrapper.find(".document-tree-cell-updatedate").exists()).toBeFalsy();
            });

            it("Should render the progress bar when the quick look pane is open and a new version of the item is being uploaded", () => {
                item.is_uploading_new_version = true;

                const wrapper = getFolderContentRowInstance({
                    item,
                    is_quick_look_displayed: true,
                });

                expect(
                    wrapper.find("[data-test=progress-bar-quick-look-pane-open]").exists(),
                ).toBeTruthy();

                expect(wrapper.find(".document-tree-cell-owner").exists()).toBeFalsy();
                expect(wrapper.find(".document-tree-cell-updatedate").exists()).toBeFalsy();
            });
        });

        describe("When the quick-look pane is closed", () => {
            it("Should render the progress bar when the quick look pane is closed and the item is being uploaded in a collapsed folder", () => {
                item.is_uploading_in_collapsed_folder = true;

                const wrapper = getFolderContentRowInstance({
                    item,
                    is_quick_look_displayed: false,
                });

                expect(
                    wrapper.find("[data-test=progress-bar-quick-look-pane-closed]").exists(),
                ).toBeTruthy();

                expect(wrapper.find(".document-tree-cell-owner").exists()).toBeFalsy();
                expect(wrapper.find(".document-tree-cell-updatedate").exists()).toBeFalsy();
            });
        });

        it("Should render the progress bar when the quick look pane is closed and a new version of the item is being uploaded", () => {
            item.is_uploading_new_version = true;

            const wrapper = getFolderContentRowInstance({
                item,
                is_quick_look_displayed: false,
            });

            expect(
                wrapper.find("[data-test=progress-bar-quick-look-pane-closed]").exists(),
            ).toBeTruthy();

            expect(wrapper.find(".document-tree-cell-owner").exists()).toBeFalsy();
            expect(wrapper.find(".document-tree-cell-updatedate").exists()).toBeFalsy();
        });
    });

    describe("User badge and last update date rendering", () => {
        it("Should render the user badge and the last update date only when the quick look pane is closed", () => {
            const wrapper = getFolderContentRowInstance({
                item,
                is_quick_look_displayed: false,
            });

            expect(wrapper.find(".document-tree-cell-owner").exists()).toBeTruthy();
            expect(wrapper.find(".document-tree-cell-updatedate").exists()).toBeTruthy();
        });

        it("Should not render the user badge and the last update date when the quick look pane is open", () => {
            const wrapper = getFolderContentRowInstance({
                item,
                is_quick_look_displayed: true,
            });

            expect(wrapper.find(".document-tree-cell-owner").exists()).toBeFalsy();
            expect(wrapper.find(".document-tree-cell-updatedate").exists()).toBeFalsy();
        });
    });

    describe("test toggle-quick-look event emission", () => {
        it("Should emit toggle-quick-look event if no dropdown is displayed", () => {
            const wrapper = getFolderContentRowInstance({ item });
            emitter.emit("set-dropdown-shown", { is_dropdown_shown: false });

            wrapper.find("[data-test=document-folder-content-row]").trigger("click");

            expect(toggle_quick_look_calls).toHaveLength(1);
            expect(toggle_quick_look_calls[0]).toStrictEqual({
                details: { item },
            });
        });

        it("Should not emit toggle-quick-look event if a dropdown is displayed", () => {
            const wrapper = getFolderContentRowInstance({ item });
            emitter.emit("set-dropdown-shown", { is_dropdown_shown: true });

            wrapper.find("[data-test=document-folder-content-row]").trigger("click");

            expect(toggle_quick_look_calls).toHaveLength(0);
        });
    });
});
