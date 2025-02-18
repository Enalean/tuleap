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

import type { Ref } from "vue";
import { ref } from "vue";
import type { UploadPostInformation, FileIdentifier } from "@tuleap/file-upload";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import { isFreetextSection } from "@/helpers/artidoc-section.type";
import { noop } from "@/helpers/noop";

type PendingAttachment = { id: FileIdentifier; upload_url: string };

export type ManageSectionAttachmentFiles = {
    addAttachmentToWaitingList(new_pending_attachment: PendingAttachment): void;
    mergeArtifactAttachments(section: ArtidocSection, description: string): FileIdentifier[];
    getPostInformation(): UploadPostInformation;
    getWaitingListAttachments(): Ref<PendingAttachment[]>;
    setWaitingListAttachments(new_value: PendingAttachment[]): void;
};

export const getSectionAttachmentFilesManager = (
    section: ReactiveStoredArtidocSection,
    artidoc_id: number,
): ManageSectionAttachmentFiles => {
    const not_saved_yet_description_attachments: Ref<PendingAttachment[]> = ref([]);

    if (isFreetextSection(section.value)) {
        return {
            getPostInformation: () => ({
                upload_url: "/api/v1/artidoc_files",
                getUploadJsonPayload(file: File): unknown {
                    return {
                        artidoc_id,
                        name: file.name,
                        file_size: file.size,
                        file_type: file.type,
                    };
                },
            }),
            getWaitingListAttachments: () => ref(not_saved_yet_description_attachments),
            setWaitingListAttachments: noop,
            addAttachmentToWaitingList: noop,
            mergeArtifactAttachments,
        };
    }

    if (section.value.attachments === null) {
        return {
            getPostInformation: () => ({
                upload_url: "",
                getUploadJsonPayload: noop,
            }),
            getWaitingListAttachments: () => ref(not_saved_yet_description_attachments),
            setWaitingListAttachments: noop,
            addAttachmentToWaitingList: noop,
            mergeArtifactAttachments,
        };
    }

    const upload_url = section.value.attachments.upload_url;

    function addAttachmentToWaitingList(new_pending_attachment: PendingAttachment): void {
        not_saved_yet_description_attachments.value.push({
            id: new_pending_attachment.id,
            upload_url: new_pending_attachment.upload_url,
        });
    }

    function filterAttachmentsToAdd(
        attachments: PendingAttachment[],
        description: string,
    ): FileIdentifier[] {
        return attachments.reduce((result: FileIdentifier[], item: PendingAttachment) => {
            if (description.includes(item.upload_url)) {
                result.push(item.id);
            }
            return result;
        }, []);
    }

    function mergeArtifactAttachments(
        section: ArtidocSection,
        description: string,
    ): FileIdentifier[] {
        const section_artifact_attachments = section.attachments
            ? section.attachments.attachment_ids
            : [];
        const filteredAttachmentToAdd = filterAttachmentsToAdd(
            not_saved_yet_description_attachments.value,
            description,
        );
        return filteredAttachmentToAdd.concat(section_artifact_attachments);
    }

    function getWaitingListAttachments(): Ref<PendingAttachment[]> {
        return not_saved_yet_description_attachments;
    }

    function setWaitingListAttachments(new_value: PendingAttachment[]): void {
        not_saved_yet_description_attachments.value = new_value;
    }

    return {
        getPostInformation: () => ({
            upload_url,
            getUploadJsonPayload(file: File): unknown {
                return {
                    name: file.name,
                    file_size: file.size,
                    file_type: file.type,
                };
            },
        }),
        addAttachmentToWaitingList,
        mergeArtifactAttachments,
        getWaitingListAttachments,
        setWaitingListAttachments,
    };
};
