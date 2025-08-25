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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import UploadProgressBar from "./UploadProgressBar.vue";
import type { FakeItem, RootState } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import type { Action } from "vuex";

describe("UploadProgressBar", () => {
    let cancel_file_upload: MockInstance & Action<RootState, RootState>;
    let cancel_version_upload: MockInstance & Action<RootState, RootState>;
    let cancel_folder_upload: MockInstance & Action<RootState, RootState>;

    function getWrapper(item: FakeItem): VueWrapper<InstanceType<typeof UploadProgressBar>> {
        return shallowMount(UploadProgressBar, {
            props: { item },
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        cancelFileUpload: cancel_file_upload,
                        cancelVersionUpload: cancel_version_upload,
                        cancelFolderUpload: cancel_folder_upload,
                    },
                }),
            },
        });
    }

    beforeEach(() => {
        cancel_file_upload = vi.fn();
        cancel_version_upload = vi.fn();
        cancel_folder_upload = vi.fn();
    });

    it(`Given item is uploading a new version of a file
        When user click on cancel
        Then we should call cancelVersionUpload`, async () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "file",
            user_can_write: true,
            is_uploading_new_version: true,
        } as unknown as FakeItem;

        const wrapper = getWrapper(item);

        await wrapper.get("[data-test=cancel-upload]").trigger("click");

        expect(cancel_version_upload).toHaveBeenCalled();
    });

    it(`Given item is uploading a file (initial version)
        When user click on cancel
        Then we should call cancelFileUpload`, async () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "file",
            user_can_write: true,
        } as unknown as FakeItem;

        const wrapper = getWrapper(item);

        await wrapper.get("[data-test=cancel-upload]").trigger("click");

        expect(cancel_file_upload).toHaveBeenCalled();
    });

    it(`Given item is uploading a file or a version
        When user click on cancel
        Then we should call cancelFolderUpload`, async () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "folder",
            user_can_write: true,
        } as unknown as FakeItem;

        const wrapper = getWrapper(item);

        await wrapper.get("[data-test=cancel-upload]").trigger("click");

        expect(cancel_folder_upload).toHaveBeenCalled();
    });

    it(`Given item is uploading a file or a version
        When user click on cancel
        Then we should mark the component as being canceled`, async () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "folder",
            user_can_write: true,
        } as unknown as FakeItem;

        const wrapper = getWrapper(item);

        expect(wrapper.classes("document-file-upload-progress-canceled")).toBe(false);

        await wrapper.get("[data-test=cancel-upload]").trigger("click");

        expect(wrapper.classes("document-file-upload-progress-canceled")).toBe(true);
    });
});
