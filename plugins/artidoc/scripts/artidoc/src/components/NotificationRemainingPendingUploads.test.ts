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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { FileUploadsCollectionStub } from "@/helpers/stubs/FileUploadsCollectionStub";
import NotificationRemainingPendingUploads, {
    type NotificationRemainingProps,
} from "@/components/NotificationRemainingPendingUploads.vue";
import { createGettext } from "vue3-gettext";

function getWrapper(props: NotificationRemainingProps): VueWrapper {
    return shallowMount(NotificationRemainingPendingUploads, {
        props,
        global: {
            plugins: [createGettext({ silent: true })],
        },
    });
}
describe("NotificationRemainingPendingUploads", () => {
    describe("When pending uploads is empty", () => {
        it("should not display the message", () => {
            const wrapper = getWrapper({
                pending_uploads: [],
                nb_pending_upload_to_display: 3,
            });

            expect(wrapper.find('span[class="tlp-alert-info"]').exists()).toBe(false);
        });
    });
    describe("When the number of downloads is not greater than the number to display", () => {
        it("should not display the message", () => {
            const pending_uploads =
                FileUploadsCollectionStub.withUploadsInProgress().pending_uploads.value;
            const wrapper = getWrapper({
                pending_uploads: pending_uploads,
                nb_pending_upload_to_display: pending_uploads.length,
            });

            expect(wrapper.find('span[class="tlp-alert-info"]').exists()).toBe(false);
        });
    });
    describe("When the number of downloads is greater than the number to display", () => {
        describe("When it's only one remaining upload", () => {
            it("should display the message in singular form", () => {
                const pending_uploads =
                    FileUploadsCollectionStub.withUploadsInProgress().pending_uploads.value;
                const wrapper = getWrapper({
                    pending_uploads: pending_uploads,
                    nb_pending_upload_to_display: pending_uploads.length - 1,
                });

                const message = wrapper.find("span");

                expect(message.exists()).toBe(true);
                expect(message.text()).toBe("There is 1 other upload in progress...");
            });
        });
        describe("When there is a bunch of remaining uploads", () => {
            it("should display the message in plural form", () => {
                const pending_uploads =
                    FileUploadsCollectionStub.withUploadsInProgress().pending_uploads.value;
                const wrapper = getWrapper({
                    pending_uploads: pending_uploads,
                    nb_pending_upload_to_display: pending_uploads.length - 2,
                });

                const message = wrapper.find("span");

                expect(message.exists()).toBe(true);
                expect(message.text()).toBe("There are 2 other uploads in progress...");
            });
        });
    });
});
