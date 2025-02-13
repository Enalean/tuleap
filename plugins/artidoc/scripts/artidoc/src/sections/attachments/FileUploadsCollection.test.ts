/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { v4 as uuidv4 } from "uuid";
import { getFileUploadsCollection } from "@/sections/attachments/FileUploadsCollection";
import type { FileUploadsCollection } from "@/sections/attachments/FileUploadsCollection";

const section_id = uuidv4();

describe("FileUploadsCollection", () => {
    let collection: FileUploadsCollection;

    beforeEach(() => {
        collection = getFileUploadsCollection();
    });

    describe("addPendingUpload", () => {
        it("should given a filename and a section id, then it should register a new file upload", () => {
            const file_name = "bug.png";
            collection.addPendingUpload(file_name, section_id);

            expect(collection.pending_uploads.value).toHaveLength(1);
            expect(collection.pending_uploads.value[0]).toStrictEqual({
                file_id: expect.any(String),
                file_name,
                progress: 0,
                section_id,
            });
        });
    });

    describe("cancelSectionUploads", () => {
        it("Given a section id, then it should remove all its pending uploads", () => {
            collection.addPendingUpload("bug1.png", section_id);
            collection.addPendingUpload("bug2.png", section_id);
            collection.addPendingUpload("bug3.png", section_id);

            collection.cancelSectionUploads(section_id);

            expect(collection.pending_uploads.value).toHaveLength(0);
        });
    });

    describe("deleteUpload", () => {
        it("Given a file_id, then it should delete the associated upload", () => {
            collection.addPendingUpload("bug1.png", section_id);

            const file_id = collection.pending_uploads.value[0].file_id;
            collection.deleteUpload(file_id);

            expect(collection.pending_uploads.value).toHaveLength(0);
        });
    });
});
