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

import { nextTick } from "vue";

const emitMock = jest.fn();
jest.mock("../../../../helpers/emitter", () => {
    return {
        emit: emitMock,
    };
});

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DownloadFolderAsZip from "./DownloadFolderAsZip.vue";
import * as location_helper from "../../../../helpers/location-helper";
import * as platform_detector from "../../../../helpers/platform-detector";
import type { ConfigurationState } from "../../../../store/configuration";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import type { PropertiesState } from "../../../../store/properties/module";
import type { Folder } from "../../../../type";

describe("DownloadFolderAsZip", () => {
    let load_properties: jest.Mock, item: Folder;

    beforeEach(() => {
        emitMock.mockClear();
        load_properties = jest.fn();
        item = { id: 10, type: "folder" } as Folder;
    });

    function getWrapper(
        max_archive_size = 1,
    ): VueWrapper<InstanceType<typeof DownloadFolderAsZip>> {
        return shallowMount(DownloadFolderAsZip, {
            props: { item },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                project_name: "tuleap-documentation",
                                max_archive_size,
                                warning_threshold: 0.5,
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                        properties: {
                            state: {
                                has_loaded_properties: true,
                            } as unknown as PropertiesState,
                            actions: {
                                getFolderProperties: load_properties,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    it("Opens the modal when the folder size exceeds the max_archive_size threshold", async () => {
        load_properties.mockImplementation(() => {
            return Promise.resolve({ total_size: 2000000 });
        });
        const wrapper = getWrapper();
        wrapper.trigger("click");

        await nextTick();
        await nextTick();

        expect(load_properties).toHaveBeenCalledWith(expect.anything(), item);
        expect(emitMock).toHaveBeenCalledWith("show-max-archive-size-threshold-exceeded-modal", {
            detail: { current_folder_size: 2000000 },
        });
    });

    it("Opens the warning modal when the size exceeds the warning_threshold", async () => {
        load_properties.mockImplementation(() => {
            return Promise.resolve({ total_size: 600000, nb_files: 100000 });
        });
        const wrapper = getWrapper();
        wrapper.trigger("click");

        await nextTick();
        await nextTick();

        expect(load_properties).toHaveBeenCalledWith(expect.anything(), item);
        expect(emitMock).toHaveBeenCalledWith("show-archive-size-warning-modal", {
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
        load_properties.mockImplementation(() => {
            return Promise.resolve({ total_size: four_GB });
        });
        const wrapper = getWrapper(2 * four_GB);

        jest.spyOn(platform_detector, "isPlatformOSX").mockReturnValue(true);

        wrapper.trigger("click");

        await nextTick();
        await nextTick();

        expect(load_properties).toHaveBeenCalledWith(expect.anything(), item);
        expect(emitMock).toHaveBeenCalledWith("show-archive-size-warning-modal", {
            detail: {
                current_folder_size: four_GB,
                should_warn_osx_user: true,
                folder_href:
                    "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip",
            },
        });
    });

    it("Opens the warning modal when user is on OSX and archive size contains more than 64k files", async () => {
        load_properties.mockImplementation(() => {
            return Promise.resolve({ total_size: 600000, nb_files: 100000 });
        });
        const wrapper = getWrapper();

        jest.spyOn(platform_detector, "isPlatformOSX").mockReturnValue(true);

        wrapper.trigger("click");

        await nextTick();
        await nextTick();
        await nextTick();

        expect(load_properties).toHaveBeenCalledWith(expect.anything(), item);
        expect(emitMock).toHaveBeenCalledWith("show-archive-size-warning-modal", {
            detail: {
                current_folder_size: 600000,
                should_warn_osx_user: true,
                folder_href:
                    "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip",
            },
        });
    });

    it(`Sets the location to the download URI instead of simply using href
        so that people can't just skip the max threshold modal`, async () => {
        load_properties.mockImplementation(() => {
            return Promise.resolve({ total_size: 10 });
        });
        const redirect = jest.spyOn(location_helper, "redirectToUrl").mockImplementation(() => {
            //Do nothing
        });
        const wrapper = getWrapper();

        wrapper.get("[data-test=download-as-zip-button]").trigger("click");
        await nextTick();
        await nextTick();
        await nextTick();

        expect(load_properties).toHaveBeenCalledWith(expect.anything(), item);
        expect(emitMock).not.toHaveBeenCalled();
        expect(redirect).toHaveBeenCalledWith(
            "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip",
        );
    });
});
