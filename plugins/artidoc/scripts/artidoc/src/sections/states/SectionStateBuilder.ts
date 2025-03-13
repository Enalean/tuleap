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

import type { Ref, ComputedRef } from "vue";
import { computed, ref } from "vue";
import {
    isArtifactSection,
    isFreetextSection,
    isPendingArtifactSection,
    isPendingSection,
} from "@/helpers/artidoc-section.type";
import type { OnGoingUploadFileWithId } from "@/sections/attachments/FileUploadsCollection";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { Level } from "@/sections/levels/SectionsNumberer";

export type SectionState = {
    is_image_upload_allowed: ComputedRef<boolean>;
    is_section_editable: ComputedRef<boolean>;
    is_save_allowed: ComputedRef<boolean>;
    is_section_in_edit_mode: Ref<boolean>;
    has_title_level_been_changed: Ref<boolean>;
    is_just_refreshed: Ref<boolean>;
    is_being_saved: Ref<boolean>;
    is_just_saved: Ref<boolean>;
    is_in_error: Ref<boolean>;
    is_outdated: Ref<boolean>;
    is_not_found: Ref<boolean>;
    error_message: Ref<string>;
    edited_title: Ref<string>;
    edited_description: Ref<string>;
    initial_level: Ref<Level>;
    is_editor_reset_needed: Ref<boolean>;
};

export type BuildSectionState = {
    forSection(section: ReactiveStoredArtidocSection): SectionState;
};

export const getSectionStateBuilder = (
    can_user_edit_document: boolean,
    pending_uploads: Ref<OnGoingUploadFileWithId[]>,
): BuildSectionState => {
    return {
        forSection: (section: ReactiveStoredArtidocSection): SectionState => ({
            is_image_upload_allowed: computed(() => {
                if (isFreetextSection(section.value)) {
                    return false;
                }

                return section.value.attachments !== null;
            }),
            is_section_editable: computed(() => {
                if (isPendingArtifactSection(section.value) || isFreetextSection(section.value)) {
                    return can_user_edit_document;
                }

                if (isArtifactSection(section.value) && section.value.can_user_edit_section) {
                    return can_user_edit_document;
                }

                return false;
            }),
            is_save_allowed: computed(
                () =>
                    !pending_uploads.value.some(
                        (upload: OnGoingUploadFileWithId) =>
                            upload.section_id === section.value.id && upload.progress < 100,
                    ),
            ),
            is_section_in_edit_mode: ref(isPendingSection(section.value)),
            is_just_refreshed: ref(false),
            is_being_saved: ref(false),
            is_just_saved: ref(false),
            is_in_error: ref(false),
            is_outdated: ref(false),
            is_not_found: ref(false),
            error_message: ref(""),
            edited_title: ref(section.value.title),
            edited_description: ref(section.value.description),
            initial_level: ref(section.value.level),
            is_editor_reset_needed: ref(false),
            has_title_level_been_changed: ref(false),
        }),
    };
};
