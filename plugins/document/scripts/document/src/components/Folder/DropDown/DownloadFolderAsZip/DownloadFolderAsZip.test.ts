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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DownloadFolderAsZip from "./DownloadFolderAsZip.vue";
import * as location_helper from "../../../../helpers/location-helper";
import * as platform_detector from "../../../../helpers/platform-detector";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import type { Folder } from "../../../../type";
import emitter from "../../../../helpers/emitter";
import { MAX_ARCHIVE_SIZE, PROJECT, WARNING_THRESHOLD } from "../../../../configuration-keys";
import { ProjectBuilder } from "../../../../../tests/builders/ProjectBuilder";

describe("DownloadFolderAsZip", () => {
    let item: Folder;
    let getFolderProperties: MockInstance;

    beforeEach(() => {
        item = { id: 10, type: "folder" } as Folder;
        getFolderProperties = vi.fn();
    });

    function getWrapper(
        max_archive_size = 1,
    ): VueWrapper<InstanceType<typeof DownloadFolderAsZip>> {
        return shallowMount(DownloadFolderAsZip, {
            props: {
                item,
                document_properties: { getFolderProperties },
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(101)
                        .withName("tuleap-documentation")
                        .build(),
                    [WARNING_THRESHOLD.valueOf()]: 0.5,
                    [MAX_ARCHIVE_SIZE.valueOf()]: max_archive_size,
                },
            },
        });
    }

    it("Opens the modal when the folder size exceeds the max_archive_size threshold", async () => {
        getFolderProperties.mockImplementation(() => {
            return Promise.resolve({ total_size: 2000000 });
        });
        const emitMock = vi.spyOn(emitter, "emit");
        const wrapper = getWrapper();
        await wrapper.trigger("click");

        expect(getFolderProperties).toHaveBeenCalledWith(expect.anything(), item);
        expect(emitMock).toHaveBeenCalledWith("show-max-archive-size-threshold-exceeded-modal", {
            detail: { current_folder_size: 2000000 },
        });
    });

    it("Opens the warning modal when the size exceeds the warning_threshold", async () => {
        getFolderProperties.mockImplementation(() => {
            return Promise.resolve({ total_size: 600000, nb_files: 100000 });
        });
        const emitMock = vi.spyOn(emitter, "emit");
        const wrapper = getWrapper();
        await wrapper.trigger("click");

        expect(getFolderProperties).toHaveBeenCalledWith(expect.anything(), item);
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
        getFolderProperties.mockImplementation(() => {
            return Promise.resolve({ total_size: four_GB });
        });
        const emitMock = vi.spyOn(emitter, "emit");
        const wrapper = getWrapper(2 * four_GB);

        vi.spyOn(platform_detector, "isPlatformOSX").mockReturnValue(true);

        await wrapper.trigger("click");

        expect(getFolderProperties).toHaveBeenCalledWith(expect.anything(), item);
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
        getFolderProperties.mockImplementation(() => {
            return Promise.resolve({ total_size: 600000, nb_files: 100000 });
        });
        const emitMock = vi.spyOn(emitter, "emit");
        const wrapper = getWrapper();

        vi.spyOn(platform_detector, "isPlatformOSX").mockReturnValue(true);

        await wrapper.trigger("click");

        expect(getFolderProperties).toHaveBeenCalledWith(expect.anything(), item);
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
        getFolderProperties.mockImplementation(() => {
            return Promise.resolve({ total_size: 10 });
        });
        const redirect = vi.spyOn(location_helper, "redirectToUrl").mockImplementation(() => {
            //Do nothing
        });
        const emitMock = vi.spyOn(emitter, "emit");
        const wrapper = getWrapper();

        await wrapper.get("[data-test=download-as-zip-button]").trigger("click");

        expect(getFolderProperties).toHaveBeenCalledWith(expect.anything(), item);
        expect(emitMock).not.toHaveBeenCalled();
        expect(redirect).toHaveBeenCalledWith(
            "/plugins/document/tuleap-documentation/folders/10/download-folder-as-zip",
        );
    });
});
