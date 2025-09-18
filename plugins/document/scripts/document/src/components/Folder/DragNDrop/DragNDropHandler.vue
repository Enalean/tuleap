<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div>
        <current-folder-drop-zone
            ref="dropzone"
            v-bind:user_can_dragndrop_in_current_folder="is_drop_possible"
            v-bind:is_dropzone_highlighted="is_dropzone_highlighted"
            v-bind:error_reason="dragover_error_reason"
        />
        <component
            v-if="error_modal_name !== null"
            v-bind:is="error_modal_name"
            v-bind:reasons="error_modal_reasons"
            v-on:error-modal-hidden="errorModalHasBeenClosed"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, onBeforeUnmount, onMounted, ref } from "vue";
import CurrentFolderDropZone from "./CurrentFolderDropZone.vue";
import { highlightItem } from "../../../helpers/highlight-items-helper";
import { isFile, isFolder } from "../../../helpers/type-check-helper";
import emitter from "../../../helpers/emitter";
import { sprintf } from "sprintf-js";
import { buildFakeItem } from "../../../helpers/item-builder";
import type { FakeItem, ItemFile, Reason, RootState } from "../../../type";
import { useState, useStore } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    IS_CHANGELOG_PROPOSED_AFTER_DND,
    IS_FILENAME_PATTERN_ENFORCED,
    MAX_FILES_DRAGNDROP,
    MAX_SIZE_UPLOAD,
    USER_CAN_DRAGNDROP,
    USER_ID,
} from "../../../configuration-keys";

const MAX_FILES_ERROR = "max_files";
const CREATION_ERROR = "creation_error";
const MAX_SIZE_ERROR = "max_size";
const ALREADY_EXISTS_ERROR = "already_exists";
const EDITION_LOCKED = "edition_locked";
const DROPPED_ITEM_IS_NOT_A_FILE = "dropped_item_is_not_a_file";
const FILENAME_PATTERN_IS_SET_ERROR = "filename_pattern_is_set";

const { $gettext } = useGettext();
const $store = useStore();

const main = ref<HTMLElement | null>(null);
const error_modal_shown = ref<string | false>(false);
const is_dropzone_highlighted = ref<boolean>(false);
const error_modal_reasons = ref<Array<Reason>>([]);
const highlighted_item_id = ref<number | null>(null);
const number_of_dragged_files = ref<number>(0);
const is_drop_possible = ref<boolean>(true);
const dragover_error_reason = ref<string>("");
const fake_item_list = ref<Array<FakeItem>>([]);

const user_id = strictInject(USER_ID);
const max_files_dragndrop = strictInject(MAX_FILES_DRAGNDROP);
const user_can_dragndrop = strictInject(USER_CAN_DRAGNDROP);
const max_size_upload = strictInject(MAX_SIZE_UPLOAD);
const is_changelog_proposed_after_dnd = strictInject(IS_CHANGELOG_PROPOSED_AFTER_DND);
const is_filename_pattern_enforced = strictInject(IS_FILENAME_PATTERN_ENFORCED);

const { current_folder, folder_content } = useState<
    Pick<RootState, "current_folder" | "folder_content">
>(["current_folder", "folder_content"]);

const user_can_dragndrop_in_current_folder = computed(
    () => user_can_dragndrop && current_folder.value && current_folder.value.user_can_write,
);
const error_modal_name = computed(() => {
    if (!error_modal_shown.value) {
        return null;
    }

    if (error_modal_shown.value === MAX_SIZE_ERROR) {
        return defineAsyncComponent(() => import("./MaxSizeDragndropErrorModal.vue"));
    }

    if (error_modal_shown.value === ALREADY_EXISTS_ERROR) {
        return defineAsyncComponent(() => import("./FileAlreadyExistsDragndropErrorModal.vue"));
    }

    if (error_modal_shown.value === CREATION_ERROR) {
        return defineAsyncComponent(() => import("./CreationErrorDragndropErrorModal.vue"));
    }

    if (error_modal_shown.value === EDITION_LOCKED) {
        return defineAsyncComponent(() => import("./DocumentLockedForEditionErrorModal.vue"));
    }

    if (error_modal_shown.value === DROPPED_ITEM_IS_NOT_A_FILE) {
        return defineAsyncComponent(() => import("./DroppedItemIsAFolderErrorModal.vue"));
    }
    if (error_modal_shown.value === FILENAME_PATTERN_IS_SET_ERROR) {
        return defineAsyncComponent(() => import("./FilenamePatternSetErrorModal.vue"));
    }
    return defineAsyncComponent(() => import("./MaxFilesDragndropErrorModal.vue"));
});

onMounted(() => {
    main.value = document.querySelector<HTMLElement>(".document-main");
    main.value?.addEventListener("dragover", ondragover);
    main.value?.addEventListener("dragleave", ondragleave);
    main.value?.addEventListener("drop", ondrop);
});

onBeforeUnmount(() => {
    main.value?.removeEventListener("dragover", ondragover);
    main.value?.removeEventListener("dragleave", ondragleave);
    main.value?.removeEventListener("drop", ondrop);
});

function ondragover(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
    if (isDragNDropingOnAModal(event)) {
        return;
    }
    if (event.dataTransfer) {
        number_of_dragged_files.value = event.dataTransfer.items.length;
    }
    is_drop_possible.value =
        isDropPossibleAccordingFilenamePattern() && user_can_dragndrop_in_current_folder.value;
    if (!is_drop_possible.value) {
        dragover_error_reason.value = getDragErrorReason();
    }
    highlightFolderDropZone(event);
}

function ondragleave(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();

    if (isInQuickLookPane()) {
        return;
    }
    is_drop_possible.value = true;
    number_of_dragged_files.value = 0;
    clearHighlight();
}

function isInQuickLookPane(): Element | null {
    return document.querySelector(
        `.quick-look-pane-highlighted,.quick-look-pane-highlighted-forbidden`,
    );
}

async function ondrop(event: DragEvent): Promise<void> {
    event.preventDefault();
    event.stopPropagation();

    if (isDragNDropingOnAModal(event)) {
        return;
    }
    const is_uploading_in_subfolder = highlighted_item_id.value !== null;
    const dropzone_item = getDropZoneItem();
    clearHighlight();

    if (!user_can_dragndrop_in_current_folder.value || !dropzone_item.user_can_write) {
        return;
    }

    if (!event.dataTransfer || !event.dataTransfer.files || event.dataTransfer.files.length === 0) {
        error_modal_shown.value = DROPPED_ITEM_IS_NOT_A_FILE;
        error_modal_reasons.value.push({ nb_dropped_files: 1 });

        return;
    }

    if (isFile(dropzone_item)) {
        await uploadNewFileVersion(event, dropzone_item);

        return;
    }

    const files = event.dataTransfer.files;

    if (files.length > max_files_dragndrop) {
        error_modal_shown.value = MAX_FILES_ERROR;
        return;
    }

    if (is_filename_pattern_enforced && files.length > 1) {
        error_modal_shown.value = FILENAME_PATTERN_IS_SET_ERROR;
        return;
    }

    if (is_filename_pattern_enforced && files.length === 1) {
        emitter.emit("show-file-creation-modal", {
            detail: {
                parent: dropzone_item,
                dropped_file: files[0],
            },
        });
        return;
    }

    for (const file of files) {
        const is_item_a_file = isDroppedItemAFile(file);
        if (!is_item_a_file) {
            error_modal_shown.value = DROPPED_ITEM_IS_NOT_A_FILE;
            error_modal_reasons.value.push({ nb_dropped_files: files.length });

            return;
        }

        if (file.size > max_size_upload) {
            error_modal_shown.value = MAX_SIZE_ERROR;
            return;
        }

        if (
            folder_content.value.find(
                (item) =>
                    item.title === file.name &&
                    !isFolder(item) &&
                    item.parent_id === dropzone_item.id,
            )
        ) {
            error_modal_shown.value = ALREADY_EXISTS_ERROR;
            return;
        }
    }

    let should_display_fake_item: boolean;
    if (!is_uploading_in_subfolder) {
        should_display_fake_item = true;
    } else {
        should_display_fake_item = dropzone_item.is_expanded;
    }

    if (is_uploading_in_subfolder && !dropzone_item.is_expanded) {
        $store.commit("toggleCollapsedFolderHasUploadingContent", {
            collapsed_folder: dropzone_item,
            toggle: true,
        });
    }

    for (const file of files) {
        try {
            fake_item_list.value.push(buildFakeItem());
            await $store.dispatch("addNewUploadFile", [
                file,
                dropzone_item,
                file.name,
                "",
                should_display_fake_item,
                fake_item_list.value[fake_item_list.value.length - 1],
            ]);
        } catch (error) {
            error_modal_shown.value = CREATION_ERROR;
            error_modal_reasons.value.push({ filename: file.name, message: error });
        }
    }
}

function errorModalHasBeenClosed() {
    error_modal_shown.value = false;
    error_modal_reasons.value = [];
}

function isDragNDropingOnAModal(event: DragEvent): boolean {
    return Boolean(event.target.closest(".tlp-modal"));
}

function clearHighlight(): void {
    const highlighted_items = document.querySelectorAll(
        `.document-tree-item-highlighted,.document-tree-item-hightlighted-forbidden,.quick-look-pane-highlighted,.quick-look-pane-highlighted-forbidden`,
    );

    for (const element of highlighted_items) {
        element.classList.remove(
            "document-tree-item-highlighted",
            "document-folder-highlighted",
            "document-file-highlighted",
            "document-tree-item-hightlighted-forbidden",
            "quick-look-pane-highlighted",
            "quick-look-pane-highlighted-forbidden",
        );
    }

    is_dropzone_highlighted.value = false;
    highlighted_item_id.value = null;
}

function highlightFolderDropZone(event: DragEvent): void {
    clearHighlight();

    const target_drop_zones = [
        ".document-tree-item-folder",
        ".document-quick-look-folder-dropzone",
        ".document-quick-look-file-dropzone",
    ];

    if (event.dataTransfer && event.dataTransfer.items.length === 1) {
        target_drop_zones.push(".document-tree-item-file");
    }

    const closest_row = event.target.closest(target_drop_zones);

    if (closest_row) {
        highlighted_item_id.value = parseInt(closest_row.dataset.itemId, 10);

        const item = getDropZoneItem();

        highlightItem(item, closest_row);
    } else {
        is_dropzone_highlighted.value = true;
    }
}

function getDropZoneItem() {
    if (!highlighted_item_id.value) {
        return current_folder.value;
    }

    return folder_content.value.find((item) => item.id === highlighted_item_id.value);
}

async function uploadNewFileVersion(event: DragEvent, dropzone_item: ItemFile): Promise<void> {
    const { lock_info, approval_table } = dropzone_item;
    const is_document_locked_by_current_user =
        lock_info === null || lock_info.lock_by.id === user_id;

    if (!is_document_locked_by_current_user) {
        error_modal_shown.value = EDITION_LOCKED;
        error_modal_reasons.value.push({
            filename: dropzone_item.title,
            lock_owner: lock_info.lock_by,
        });

        return;
    }

    const files = event.dataTransfer.files;
    const file = files[0];

    const is_item_a_file = isDroppedItemAFile(file);
    if (!is_item_a_file) {
        error_modal_shown.value = DROPPED_ITEM_IS_NOT_A_FILE;
        error_modal_reasons.value.push({ nb_dropped_files: 1 });

        return;
    }

    if (file.size > max_size_upload) {
        error_modal_shown.value = MAX_SIZE_ERROR;
        return;
    }

    try {
        if (is_changelog_proposed_after_dnd.value || approval_table !== null) {
            emitter.emit("show-changelog-modal", {
                detail: {
                    updated_file: dropzone_item,
                    dropped_file: file,
                },
            });

            return;
        }

        await $store.dispatch("createNewFileVersion", [dropzone_item, file]);
    } catch (error) {
        error_modal_shown.value = CREATION_ERROR;
        error_modal_reasons.value.push({ filename: file.name, message: error });
    }
}

function isDroppedItemAFile(file): boolean {
    return file.size % 4096 !== 0 || file.type !== "";
}

function isDropPossibleAccordingFilenamePattern(): boolean {
    return (
        (is_filename_pattern_enforced && number_of_dragged_files.value === 1) ||
        !is_filename_pattern_enforced
    );
}

function getDragErrorReason(): string {
    if (is_filename_pattern_enforced && number_of_dragged_files.value > 1) {
        return $gettext(
            "When a filename pattern is set, you are not allowed to drag 'n drop more than 1 file at once.",
        );
    }
    return sprintf($gettext("Dropping files in %s is forbidden."), current_folder.value?.title);
}
</script>
