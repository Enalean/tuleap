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
import * as location_helper from "../../../../helpers/location-helper.js";
import Vue from "vue";

describe("DownloadFolderAsZip", () => {
    let store;

    function getWrapper(max_archive_size = 1) {
        const state = {
            project_name: "tuleap-documentation",
            max_archive_size,
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
                nb_files: 50,
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
                should_warn_osx_user: false,
                folder_href:
                    "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip",
            },
        });
    });

    it("Opens the warning modal when user is on OSX and archive size exceeds or equals 4GB", async () => {
        const four_GB = 4 * Math.pow(10, 9);
        const wrapper = getWrapper(2 * four_GB);
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const navigator = window.navigator;

        delete window.navigator;

        window.navigator = {
            platform: "MacIntel",
        };

        jest.spyOn(store, "dispatch").mockReturnValue(
            Promise.resolve({
                total_size: four_GB,
                nb_files: 50,
            })
        );

        wrapper.trigger("click");

        await Vue.nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("getFolderProperties", [
            { id: 10, type: "folder" },
        ]);
        expect(event_bus_emit).toHaveBeenCalledWith("show-archive-size-warning-modal", {
            detail: {
                current_folder_size: four_GB,
                should_warn_osx_user: true,
                folder_href:
                    "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip",
            },
        });

        window.navigator = navigator;
    });

    it("Opens the warning modal when user is on OSX and archive size contains more than 64k files", async () => {
        const wrapper = getWrapper();
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const navigator = window.navigator;

        delete window.navigator;

        window.navigator = {
            platform: "MacIntel",
        };

        jest.spyOn(store, "dispatch").mockReturnValue(
            Promise.resolve({
                total_size: 600000,
                nb_files: 65000,
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
                should_warn_osx_user: true,
                folder_href:
                    "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip",
            },
        });
        window.navigator = navigator;
    });

    it(`Sets the location to the download URI instead of simply using href
        so that people can't just skip the max threshold modal`, async () => {
        const redirect = jest.spyOn(location_helper, "redirectToUrl").mockImplementation(() => {});
        const wrapper = getWrapper();
        const eventBusEmit = jest.spyOn(EventBus, "$emit");
        jest.spyOn(store, "dispatch").mockResolvedValue({
            total_size: 10000,
        });

        await wrapper.vm.checkFolderSize();

        expect(store.dispatch).toHaveBeenCalledWith("getFolderProperties", [
            { id: 10, type: "folder" },
        ]);
        expect(eventBusEmit).not.toHaveBeenCalled();
        expect(redirect).toHaveBeenCalledWith(
            "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip"
        );
    });
});
