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
import NotificationProgress from "@/components/section/description/NotificationProgress.vue";
import NotificationMessage from "@/components/section/description/NotificationMessage.vue";
import { NOTIFICATION_STORE } from "@/stores/notification-injection-key";
import type { UseNotificationsStoreType } from "@/stores/useNotificationsStore";
import { NotificationsSub } from "@/helpers/stubs/NotificationsStub";
import NotificationRemainingPendingUploads from "@/components/NotificationRemainingPendingUploads.vue";

describe("NotificationContainer", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;
    let mocked_notifications_data: UseNotificationsStoreType;

    beforeAll(() => {
        mocked_notifications_data = NotificationsSub.withMessages();
        mockStrictInject([
            [UPLOAD_FILE_STORE, UploadFileStoreStub.uploadInProgress()],
            [NOTIFICATION_STORE, mocked_notifications_data],
        ]);
        wrapper = shallowMount(NotificationContainer, {});
    });

    it("should display a notification progress for each pending uploads", () => {
        expect(wrapper.findAllComponents(NotificationProgress)).toHaveLength(3);
    });

    it("should display a notification for each messages", () => {
        expect(wrapper.findAllComponents(NotificationMessage)).toHaveLength(2);
    });

    it("should display info message about remaining pending uploads", () => {
        expect(wrapper.findAllComponents(NotificationRemainingPendingUploads)).toHaveLength(1);
    });
});
