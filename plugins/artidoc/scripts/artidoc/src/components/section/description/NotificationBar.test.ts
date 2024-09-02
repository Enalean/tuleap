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
import NotificationBar from "@/components/section/description/NotificationBar.vue";
import type { NotificationBarProps } from "@/components/section/description/NotificationBar.vue";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { UPLOAD_FILE_STORE } from "@/stores/upload-file-store-injection-key";
import { UploadFileStoreStub } from "@/helpers/stubs/UploadFileStoreStub";

const default_props = {
    file_id: "123",
    file_name: "file_name",
    upload_progress: 0,
    is_in_progress: false,
    reset_progress: vi.fn(),
};

function getWrapper(props: NotificationBarProps): VueWrapper {
    return shallowMount(NotificationBar, {
        props,
    });
}

describe("NotificationBar", () => {
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

            const progressElement = wrapper.find('span[class="tlp-alert-info"]');

            expect(progressElement.exists()).toBe(true);
            expect(progressElement.text()).toBe("file_name : 20%");
        });
    });
    describe("when an error occurred", () => {
        it("should display the message", () => {
            const wrapper = getWrapper({
                ...default_props,
                message: "a message",
            });

            const messageElement = wrapper.find('span[class="tlp-alert-danger"]');

            expect(messageElement.exists()).toBe(true);
            expect(messageElement.text()).toBe("a message");
        });
    });
});
