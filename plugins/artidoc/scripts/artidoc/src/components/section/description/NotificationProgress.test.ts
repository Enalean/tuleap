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
import { v4 as uuidv4 } from "uuid";
import { noop } from "@/helpers/noop";
import type { FileUploadsCollection } from "@/sections/attachments/FileUploadsCollection";
import { FILE_UPLOADS_COLLECTION } from "@/sections/attachments/sections-file-uploads-collection-injection-key";
import { FileUploadsCollectionStub } from "@/helpers/stubs/FileUploadsCollectionStub";
import NotificationProgress from "@/components/section/description/NotificationProgress.vue";

const file_id = uuidv4();
const file_name = "bug.png";

describe("NotificationProgress", () => {
    let file_uploads_collection: FileUploadsCollection, upload_progress_in_percents: number;

    beforeEach(() => {
        file_uploads_collection = FileUploadsCollectionStub.withUploadsInProgress();
        upload_progress_in_percents = 0;
    });

    function getWrapper(): VueWrapper {
        return shallowMount(NotificationProgress, {
            global: {
                provide: {
                    [FILE_UPLOADS_COLLECTION.valueOf()]: file_uploads_collection,
                },
            },
            props: {
                file_id,
                file_name,
                is_in_progress: upload_progress_in_percents > 0,
                reset_progress: noop,
                upload_progress: upload_progress_in_percents,
            },
        });
    }

    describe("when an upload is in progress", () => {
        it("should display the progress bar", () => {
            upload_progress_in_percents = 20;

            const progress_element = getWrapper().find('div[class="notification-message"]');

            expect(progress_element.exists()).toBe(true);
            expect(progress_element.text()).toBe(`${file_name} ${upload_progress_in_percents}%`);
        });
    });
    describe("when the upload is finished", () => {
        it("Given an upload, When it is done (100%), Then it should delete the progress bar after 3 seconds", () => {
            vi.useFakeTimers();
            vi.spyOn(file_uploads_collection, "deleteUpload");

            upload_progress_in_percents = 100;

            getWrapper();
            vi.advanceTimersByTime(3_000);

            expect(file_uploads_collection.deleteUpload).toHaveBeenCalledOnce();
            expect(file_uploads_collection.deleteUpload).toHaveBeenCalledWith(file_id);

            vi.useRealTimers();
        });
    });
});
