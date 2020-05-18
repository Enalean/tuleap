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
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../../../helpers/local-vue.js";
import DownloadFolderAsZip from "./DownloadFolderAsZip.vue";
import EventBus from "../../../../helpers/event-bus.js";
import Vue from "vue";

describe("DownloadFolderAsZip", () => {
    let store;

    function getWrapper() {
        const state = {
            project_name: "tuleap-documentation",
            max_archive_size: 1,
            warning_threshold: 0.5,
        };
        const store_options = { state };
        store = createStoreMock(store_options);

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

    it("Opens the modal when the folder size exceeds the max_archive_size threshold", async () => {
        const wrapper = getWrapper();
        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        jest.spyOn(store, "dispatch").mockReturnValue(
            Promise.resolve({
                total_size: 2000000,
            })
        );

        wrapper.trigger("click");

        await Vue.nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("getFolderProperties", [
            { id: 10, type: "folder" },
        ]);
        expect(event_bus_emit).toHaveBeenCalledWith(
            "show-max-archive-size-threshold-exceeded-modal",
            {
                detail: { current_folder_size: 2000000 },
            }
        );
    });

    it("Opens the warning modal when the size exceeds the warning_threshold", async () => {
        const wrapper = getWrapper();
        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        jest.spyOn(store, "dispatch").mockReturnValue(
            Promise.resolve({
                total_size: 600000,
            })
        );

        wrapper.trigger("click");

        await Vue.nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("getFolderProperties", [
            { id: 10, type: "folder" },
        ]);
        expect(event_bus_emit).toHaveBeenCalledWith("show-archive-size-warning-modal", {
            detail: {
                current_folder_size: 600000,
                folder_href:
                    "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip",
            },
        });
    });

    it("Downloads the zip", async () => {
        const wrapper = getWrapper();

        delete window.location;
        window.location = {
            assign: jest.fn(),
        };

        jest.spyOn(store, "dispatch").mockReturnValue(
            Promise.resolve({
                total_size: 10000,
            })
        );

        wrapper.trigger("click");

        await Vue.nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("getFolderProperties", [
            { id: 10, type: "folder" },
        ]);
        expect(window.location.assign).toHaveBeenCalledWith(
            "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip"
        );
    });
});
