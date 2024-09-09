/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import NotificationProgress from "@/components/section/description/NotificationProgress.vue";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { UPLOAD_FILE_STORE } from "@/stores/upload-file-store-injection-key";
import { UploadFileStoreStub } from "@/helpers/stubs/UploadFileStoreStub";
import type { NotificationProgressProps } from "@/components/section/description/NotificationProgress.vue";

const default_props = {
    file_id: "123",
    file_name: "file_name",
    upload_progress: 0,
    is_in_progress: false,
    reset_progress: vi.fn(),
};

function getWrapper(props: NotificationProgressProps): VueWrapper {
    return shallowMount(NotificationProgress, {
        props,
    });
}

describe("NotificationProgress", () => {
    const mocked_upload_data = UploadFileStoreStub.uploadInProgress();

    beforeEach(() => {
        mockStrictInject([[UPLOAD_FILE_STORE, mocked_upload_data]]);
    });

    describe("when an upload is in progress", () => {
        it("should display the progress bar", () => {
            const wrapper = getWrapper({
                ...default_props,
                upload_progress: 20,
            });

            const progressElement = wrapper.find('div[class="notification-message"]');

            expect(progressElement.exists()).toBe(true);
            expect(progressElement.text()).toBe("file_name 20%");
        });
    });
    describe("when the upload is finished", () => {
        it("should delete the progress bar after 3 seconds", () => {
            vi.useFakeTimers();

            const mocked_delete_upload = vi.fn();
            mockStrictInject([
                [UPLOAD_FILE_STORE, { ...mocked_upload_data, deleteUpload: mocked_delete_upload }],
            ]);

            getWrapper({
                ...default_props,
                upload_progress: 100,
            });

            vi.advanceTimersByTime(3_000);

            expect(mocked_delete_upload).toHaveBeenCalledOnce();

            vi.useRealTimers();
        });
    });
});
