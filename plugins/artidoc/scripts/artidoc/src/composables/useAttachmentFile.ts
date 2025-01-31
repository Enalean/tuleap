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

import type { Ref } from "vue";
import { ref } from "vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { isFreetextSection } from "@/helpers/artidoc-section.type";
import { noop } from "@/helpers/noop";
import type { FileUploadOptions } from "@tuleap/file-upload";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";

type PendingAttachment = { id: number; upload_url: string };
export interface AttachmentFile {
    addAttachmentToWaitingList: (new_pending_attachment: PendingAttachment) => void;
    mergeArtifactAttachments: (
        section: ArtidocSection,
        description: string,
    ) => { field_id: number; value: number[] };
    post_information: FileUploadOptions["post_information"];
    getWaitingListAttachments: () => Ref<PendingAttachment[]>;
    setWaitingListAttachments: (new_value: PendingAttachment[]) => void;
}

export function useAttachmentFile(
    section: ReactiveStoredArtidocSection,
    artidoc_id: number,
): AttachmentFile {
    const not_saved_yet_description_attachments: Ref<PendingAttachment[]> = ref([]);

    if (isFreetextSection(section.value)) {
        return {
            post_information: {
                upload_url: "/api/v1/artidoc_files",
                getUploadJsonPayload(file: File): unknown {
                    return {
                        artidoc_id,
                        name: file.name,
                        file_size: file.size,
                        file_type: file.type,
                    };
                },
            },
            getWaitingListAttachments: () => ref(not_saved_yet_description_attachments),
            setWaitingListAttachments: noop,
            addAttachmentToWaitingList: noop,
            mergeArtifactAttachments,
        };
    }

    const field_id = section.value.attachments ? section.value.attachments.field_id : 0;
    if (field_id === 0) {
        return {
            post_information: {
                upload_url: "",
                getUploadJsonPayload: noop,
            },
            getWaitingListAttachments: () => ref(not_saved_yet_description_attachments),
            setWaitingListAttachments: noop,
            addAttachmentToWaitingList: noop,
            mergeArtifactAttachments,
        };
    }

    const upload_url = `/api/v1/tracker_fields/${field_id}/files`;

    function addAttachmentToWaitingList(new_pending_attachment: PendingAttachment): void {
        not_saved_yet_description_attachments.value.push({
            id: new_pending_attachment.id,
            upload_url: new_pending_attachment.upload_url,
        });
    }

    function filterAttachmentsToAdd(
        attachments: PendingAttachment[],
        description: string,
    ): number[] {
        return attachments.reduce((result: number[], item: PendingAttachment) => {
            if (description.includes(item.upload_url)) {
                result.push(item.id);
            }
            return result;
        }, []);
    }

    function mergeArtifactAttachments(
        section: ArtidocSection,
        description: string,
    ): {
        field_id: number;
        value: number[];
    } {
        const section_artifact_attachments = section.attachments
            ? section.attachments.file_descriptions.map((file_description) => file_description.id)
            : [];
        const filteredAttachmentToAdd = filterAttachmentsToAdd(
            not_saved_yet_description_attachments.value,
            description,
        );
        return {
            field_id,
            value: filteredAttachmentToAdd.concat(section_artifact_attachments),
        };
    }

    function getWaitingListAttachments(): Ref<PendingAttachment[]> {
        return not_saved_yet_description_attachments;
    }

    function setWaitingListAttachments(new_value: PendingAttachment[]): void {
        not_saved_yet_description_attachments.value = new_value;
    }

    return {
        post_information: {
            upload_url,
            getUploadJsonPayload(file: File): unknown {
                return {
                    name: file.name,
                    file_size: file.size,
                    file_type: file.type,
                };
            },
        },
        addAttachmentToWaitingList,
        mergeArtifactAttachments,
        getWaitingListAttachments,
        setWaitingListAttachments,
    };
}
