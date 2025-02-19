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

import { describe, it, expect } from "vitest";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { getSectionAttachmentFilesManager } from "@/sections/attachments/SectionAttachmentFilesManager";

const section = ArtifactSectionFactory.create();
const artidoc_id = 123;

describe("SectionAttachmentFilesManager", () => {
    it("should return formatted upload_url", () => {
        const { upload_url } = getSectionAttachmentFilesManager(
            ReactiveStoredArtidocSectionStub.fromSection(section),
            artidoc_id,
        ).getPostInformation();
        expect(upload_url).toBe("/api/v1/tracker_fields/171/files");
    });

    it("should return a specific upload url for freetext sections", () => {
        const { upload_url } = getSectionAttachmentFilesManager(
            ReactiveStoredArtidocSectionStub.fromSection(FreetextSectionFactory.create()),
            artidoc_id,
        ).getPostInformation();
        expect(upload_url).toBe("/api/v1/artidoc_files");
    });

    describe("getWaitingListAttachments", () => {
        it("should return the waiting list", () => {
            const { getWaitingListAttachments } = getSectionAttachmentFilesManager(
                ReactiveStoredArtidocSectionStub.fromSection(section),
                artidoc_id,
            );

            expect(getWaitingListAttachments().value).toEqual([]);
        });
    });

    describe("addAttachmentToWaitingList", () => {
        it("should add a new attachment to the waiting list", () => {
            const { addAttachmentToWaitingList, getWaitingListAttachments } =
                getSectionAttachmentFilesManager(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    artidoc_id,
                );
            expect(getWaitingListAttachments().value).toEqual([]);

            const attachment_id_to_add = 123;
            const upload_url = "/path/to/upload";

            addAttachmentToWaitingList({ id: attachment_id_to_add, upload_url });

            expect(getWaitingListAttachments().value).toEqual([
                { id: attachment_id_to_add, upload_url },
            ]);
        });
    });

    describe("mergeArtifactAttachments", () => {
        describe("when all pending images are present in the current description", () => {
            it("should get all files to upload", () => {
                const { mergeArtifactAttachments, setWaitingListAttachments } =
                    getSectionAttachmentFilesManager(
                        ReactiveStoredArtidocSectionStub.fromSection(section),
                        artidoc_id,
                    );

                setWaitingListAttachments([
                    { id: 123, upload_url: "/path/to/foo.png" },
                    { id: 456, upload_url: "/path/to/bar.png" },
                ]);

                const current_description =
                    '<img src="/path/to/foo.png" /> some content <img src="/path/to/bar.png" />';

                expect(mergeArtifactAttachments(section, current_description)).toStrictEqual([
                    123, 456,
                ]);
            });
        });
        describe("when some pending images has been removed in the current description", () => {
            it("should get only files present in the description", () => {
                const { mergeArtifactAttachments, setWaitingListAttachments } =
                    getSectionAttachmentFilesManager(
                        ReactiveStoredArtidocSectionStub.fromSection(section),
                        artidoc_id,
                    );

                setWaitingListAttachments([
                    { id: 123, upload_url: "/path/to/foo.png" },
                    { id: 456, upload_url: "/path/to/bar.png" },
                ]);

                const current_description = '<img src="/path/to/foo.png" /> some content';

                expect(mergeArtifactAttachments(section, current_description)).toStrictEqual([123]);
            });
        });
    });

    describe("setWaitingListAttachments", () => {
        it("should set new waiting file list", () => {
            const { getWaitingListAttachments, setWaitingListAttachments } =
                getSectionAttachmentFilesManager(
                    ReactiveStoredArtidocSectionStub.fromSection(section),
                    artidoc_id,
                );

            expect(getWaitingListAttachments().value).toEqual([]);

            const waiting_list = [
                { id: 123, upload_url: "/path/to/foo.png" },
                { id: 456, upload_url: "/path/to/bar.png" },
            ];
            setWaitingListAttachments(waiting_list);

            expect(getWaitingListAttachments().value).toEqual(waiting_list);
        });
    });
});
