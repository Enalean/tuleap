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
import { computed, ref } from "vue";
import type { Ref, ComputedRef } from "vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import {
    isFreetextSection,
    isArtifactSection,
    isPendingArtifactSection,
    isPendingSection,
} from "@/helpers/artidoc-section.type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import useSaveSection from "@/composables/useSaveSection";
import type { EditorErrors } from "@/composables/useEditorErrors";
import { useEditorErrors } from "@/composables/useEditorErrors";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";
import { useEditorSectionContent } from "@/composables/useEditorSectionContent";
import type { RefreshSection } from "@/composables/useRefreshSection";
import { useRefreshSection } from "@/composables/useRefreshSection";
import type { AttachmentFile } from "@/composables/useAttachmentFile";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import { EDITORS_COLLECTION } from "@/stores/useSectionEditorsStore";
import { UPLOAD_FILE_STORE } from "@/stores/upload-file-store-injection-key";
import type { Fault } from "@tuleap/fault";
import type { ReplacePendingSections } from "@/stores/PendingSectionsReplacer";
import type { UpdateSections } from "@/stores/SectionsUpdater";

export type SectionEditorActions = {
    enableEditor: () => void;
    saveEditor: () => void;
    forceSaveEditor: () => void;
    cancelEditor: () => void;
    refreshSection: RefreshSection["refreshSection"];
    deleteSection: () => void;
};

export type EditorState = {
    is_image_upload_allowed: ComputedRef<boolean>;
    is_section_editable: ComputedRef<boolean>;
    is_section_in_edit_mode: Ref<boolean>;
    is_save_allowed: Ref<boolean>;
    isJustRefreshed: () => boolean;
    isBeingSaved: () => boolean;
    isJustSaved: () => boolean;
};

export type SectionEditor = {
    editor_state: EditorState;
    editor_error: EditorErrors;
    editor_actions: SectionEditorActions;
    editor_section_content: EditorSectionContent;
};

export function useSectionEditor(
    section: ArtidocSection,
    mergeArtifactAttachments: AttachmentFile["mergeArtifactAttachments"],
    setWaitingListAttachments: AttachmentFile["setWaitingListAttachments"],
    replace_pending_sections: ReplacePendingSections,
    update_sections: UpdateSections,
    is_upload_in_progress: Ref<boolean>,
    raise_delete_section_error_callback: (error_message: string) => void,
): SectionEditor {
    const editors_collection = strictInject(EDITORS_COLLECTION);
    const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);

    const current_section: Ref<ArtidocSection> = ref(section);
    const editor_errors_handler = useEditorErrors();

    const is_image_upload_allowed = computed(() => {
        if (isFreetextSection(section)) {
            return false;
        }
        return (
            current_section.value.attachments !== null &&
            undefined !== current_section.value.attachments.field_id &&
            0 !== current_section.value.attachments?.field_id
        );
    });
    const { getSectionPositionForSave, removeSection } = strictInject(SECTIONS_STORE);
    const is_section_in_edit_mode = ref(isPendingSection(current_section.value));
    const is_section_editable = computed(() => {
        if (
            isPendingArtifactSection(current_section.value) ||
            isFreetextSection(current_section.value)
        ) {
            return can_user_edit_document;
        }

        if (
            isArtifactSection(current_section.value) &&
            current_section.value.can_user_edit_section
        ) {
            return can_user_edit_document;
        }

        return false;
    });

    const updateCurrentSection = (new_value: ArtidocSection): void => {
        current_section.value = new_value;
    };

    const { refreshSection, isJustRefreshed } = useRefreshSection(
        current_section.value,
        editor_errors_handler,
        update_sections,
        {
            closeEditor: closeEditor,
            updateCurrentSection: updateCurrentSection,
        },
    );

    const setEditMode = (new_value: boolean): void => {
        if (is_section_in_edit_mode.value === new_value) {
            return;
        }

        is_section_in_edit_mode.value = new_value;
    };

    const editor_section_content = useEditorSectionContent(current_section, {
        showActionsButtons: () => {
            setEditMode(true);
        },
        hideActionsButtons: () => {
            setEditMode(false);
        },
    });

    const { save, forceSave, isBeingSaved, isJustSaved } = useSaveSection(
        editor_errors_handler,
        replace_pending_sections,
        update_sections,
        {
            updateCurrentSection,
            closeEditor,
            setEditMode,
            getSectionPositionForSave,
            mergeArtifactAttachments,
        },
    );

    const enableEditor = (): void => {
        setEditMode(true);
    };

    function closeEditor(): void {
        editor_section_content.resetContent();
        setEditMode(false);
        setWaitingListAttachments([]);

        editor_errors_handler.resetErrorStates();
    }

    const { cancelSectionUploads } = strictInject(UPLOAD_FILE_STORE);
    function cancelEditor(): void {
        closeEditor();
        cancelSectionUploads(current_section.value.id);

        if (isPendingSection(current_section.value)) {
            editors_collection.removeEditor(current_section.value);
            removeSection(current_section.value);
        }
    }

    function deleteSection(): void {
        removeSection(current_section.value).match(
            () => {
                if (is_section_in_edit_mode.value) {
                    closeEditor();
                }
                editors_collection.removeEditor(current_section.value);
            },
            (fault: Fault) => {
                if (is_section_in_edit_mode.value) {
                    editor_errors_handler.handleError(fault);
                } else {
                    raise_delete_section_error_callback(String(fault));
                }
            },
        );
    }

    const is_save_allowed = computed(() => !is_upload_in_progress.value);
    const forceSaveEditor = (): void => {
        if (!is_save_allowed.value) {
            return;
        }
        forceSave(current_section.value, {
            title: editor_section_content.editable_title.value,
            description: editor_section_content.editable_description.value,
        });
    };

    const saveEditor = (): void => {
        if (!is_save_allowed.value) {
            return;
        }
        save(current_section.value, {
            title: editor_section_content.editable_title.value,
            description: editor_section_content.editable_description.value,
        });
    };

    const editor_actions: SectionEditorActions = {
        enableEditor,
        saveEditor,
        forceSaveEditor,
        cancelEditor,
        refreshSection,
        deleteSection,
    };

    const editor: SectionEditor = {
        editor_state: {
            is_image_upload_allowed: is_image_upload_allowed,
            is_section_editable,
            is_section_in_edit_mode,
            is_save_allowed,
            isJustRefreshed,
            isJustSaved,
            isBeingSaved,
        },
        editor_actions,
        editor_error: editor_errors_handler,
        editor_section_content,
    };

    editors_collection.addEditor(section, editor);

    return editor;
}
