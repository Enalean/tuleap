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

import { beforeAll, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ComponentPublicInstance } from "vue";
import NotificationContainer from "@/components/NotificationContainer.vue";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { UPLOAD_FILE_STORE } from "@/stores/upload-file-store-injection-key";
import { UploadFileStoreStub } from "@/helpers/stubs/UploadFileStoreStub";
import NotificationBar from "@/components/section/description/NotificationBar.vue";

describe("NotificationContainer", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;

    beforeAll(() => {
        mockStrictInject([[UPLOAD_FILE_STORE, UploadFileStoreStub.uploadInProgress()]]);
        wrapper = shallowMount(NotificationContainer, {});
    });

    it("should display a notification bar for each pending uploads", () => {
        expect(wrapper.findAllComponents(NotificationBar)).toHaveLength(3);
    });
});
