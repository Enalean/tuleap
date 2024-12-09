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
import { noop } from "@/helpers/noop";

type PendingAttachment = { id: number; upload_url: string };
export interface AttachmentFile {
    addAttachmentToWaitingList: (new_pending_attachment: PendingAttachment) => void;
    mergeArtifactAttachments: (
        section: ArtidocSection,
        description: string,
    ) => { field_id: number; value: number[] };
    upload_url: string;
    getWaitingListAttachments: () => Ref<PendingAttachment[]>;
    setWaitingListAttachments: (new_value: PendingAttachment[]) => void;
}

export function useAttachmentFile(field_id: Ref<number>): AttachmentFile {
    const not_saved_yet_description_attachments: Ref<PendingAttachment[]> = ref([]);

    if (field_id.value === 0) {
        return {
            upload_url: "",
            getWaitingListAttachments: () => ref(not_saved_yet_description_attachments),
            setWaitingListAttachments: noop,
            addAttachmentToWaitingList: noop,
            mergeArtifactAttachments,
        };
    }

    const upload_url = `/api/v1/tracker_fields/${field_id.value}/files`;

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
            field_id: field_id.value,
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
        upload_url,
        addAttachmentToWaitingList,
        mergeArtifactAttachments,
        getWaitingListAttachments,
        setWaitingListAttachments,
    };
}
