<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
            v-bind:user_can_dragndrop_in_current_folder="user_can_dragndrop_in_current_folder"
            v-bind:is_dropzone_highlighted="is_dropzone_highlighted"
        />
        <component
            v-bind:is="error_modal_name"
            v-bind:reasons="error_modal_reasons"
            v-on:error-modal-hidden="errorModalHasBeenClosed"
        />
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import CurrentFolderDropZone from "./CurrentFolderDropZone.vue";
import { TYPE_FILE, TYPE_FOLDER } from "../../../constants.js";
import { highlightItem } from "../../../helpers/highlight-items-helper.js";

export default {
    components: { CurrentFolderDropZone },
    data() {
        return {
            main: null,
            error_modal_shown: false,
            is_dropzone_highlighted: false,
            error_modal_reasons: [],
            MAX_FILES_ERROR: "max_files",
            CREATION_ERROR: "creation_error",
            MAX_SIZE_ERROR: "max_size",
            ALREADY_EXISTS_ERROR: "already_exists",
            EDITION_LOCKED: "edition_locked",
            DOCUMENT_NEEDS_APPROVAL: "document_needs_approval",
            DROPPED_ITEM_IS_NOT_A_FILE: "dropped_item_is_not_a_file",
            IE11_NOT_SUPPORTED: "ie11_is_not_supported",
            highlighted_item_id: null,
        };
    },
    computed: {
        ...mapGetters(["user_can_dragndrop"]),
        ...mapState([
            "current_folder",
            "folder_content",
            "max_files_dragndrop",
            "max_size_upload",
            "user_id",
        ]),
        user_can_dragndrop_in_current_folder() {
            return (
                this.user_can_dragndrop && this.current_folder && this.current_folder.user_can_write
            );
        },
        error_modal_name() {
            if (!this.error_modal_shown) {
                return "";
            }

            if (this.error_modal_shown === this.MAX_SIZE_ERROR) {
                return () =>
                    import(
                        /* webpackChunkName: "document-max-size-dragndrop-error-modal" */ "./MaxSizeDragndropErrorModal.vue"
                    );
            }

            if (this.error_modal_shown === this.ALREADY_EXISTS_ERROR) {
                return () =>
                    import(
                        /* webpackChunkName: "document-max-size-dragndrop-error-modal" */ "./FileAlreadyExistsDragndropErrorModal.vue"
                    );
            }

            if (this.error_modal_shown === this.CREATION_ERROR) {
                return () =>
                    import(
                        /* webpackChunkName: "document-max-size-dragndrop-error-modal" */ "./CreationErrorDragndropErrorModal.vue"
                    );
            }

            if (this.error_modal_shown === this.EDITION_LOCKED) {
                return () =>
                    import(
                        /* webpackChunkName: "document-edition-locked-error-modal" */ "./DocumentLockedForEditionErrorModal.vue"
                    );
            }
            if (this.error_modal_shown === this.DOCUMENT_NEEDS_APPROVAL) {
                return () =>
                    import(
                        /* webpackChunkName: "document-needs-approval-error-modal" */ "./DocumentNeedsApprovalErrorModal.vue"
                    );
            }
            if (this.error_modal_shown === this.IE11_NOT_SUPPORTED) {
                return () =>
                    import(
                        /* webpackChunkName: "document-needs-approval-error-modal" */ "./BrowserNotSupported.vue"
                    );
            }

            if (this.error_modal_shown === this.DROPPED_ITEM_IS_NOT_A_FILE) {
                return () =>
                    import(
                        /* webpackChunkName: "document-droppped-item-is-folder-error" */ "./DroppedItemIsAFolderErrorModal.vue"
                    );
            }

            return () =>
                import(
                    /* webpackChunkName: "document-max-files-dragndrop-error-modal" */ "./MaxFilesDragndropErrorModal.vue"
                );
        },
    },
    created() {
        this.main = document.querySelector(".document-main");
        this.main.addEventListener("dragover", this.ondragover);
        this.main.addEventListener("dragleave", this.ondragleave);
        this.main.addEventListener("drop", this.ondrop);
    },
    beforeDestroy() {
        this.main.removeEventListener("dragover", this.ondragover);
        this.main.removeEventListener("dragleave", this.ondragleave);
        this.main.removeEventListener("drop", this.ondrop);
    },
    methods: {
        ondragover(event) {
            event.preventDefault();
            event.stopPropagation();
            if (this.isDragNDropingOnAModal(event)) {
                return;
            }

            this.highlightFolderDropZone(event);
        },
        ondragleave(event) {
            event.preventDefault();
            event.stopPropagation();

            if (this.isInQuickLookPane()) {
                return;
            }

            this.clearHighlight();
        },
        isInQuickLookPane() {
            return document.querySelector(`
                .quick-look-pane-highlighted,
                .quick-look-pane-highlighted-forbidden
            `);
        },
        async ondrop(event) {
            event.preventDefault();
            event.stopPropagation();
            if (this.isDragNDropingOnAModal(event)) {
                return;
            }
            const is_uploading_in_subfolder = this.highlighted_item_id !== null;
            const dropzone_item = this.getDropZoneItem();
            this.clearHighlight();

            if (!this.user_can_dragndrop_in_current_folder || !dropzone_item.user_can_write) {
                return;
            }

            if (!event.dataTransfer.files || event.dataTransfer.files.length === 0) {
                this.error_modal_shown = this.DROPPED_ITEM_IS_NOT_A_FILE;
                this.error_modal_reasons.push({ nb_dropped_files: 1 });

                return;
            }

            if (dropzone_item.type === TYPE_FILE) {
                await this.uploadNewFileVersion(event, dropzone_item);

                return;
            }

            const files = event.dataTransfer.files;

            if (files.length > this.max_files_dragndrop) {
                this.error_modal_shown = this.MAX_FILES_ERROR;
                return;
            }

            for (const file of files) {
                const is_item_a_file = await this.isDroppedItemAFile(file);
                if (!is_item_a_file) {
                    this.error_modal_shown = this.DROPPED_ITEM_IS_NOT_A_FILE;
                    this.error_modal_reasons.push({ nb_dropped_files: files.length });

                    return;
                }

                if (file.size > this.max_size_upload) {
                    this.error_modal_shown = this.MAX_SIZE_ERROR;
                    return;
                }

                if (
                    this.folder_content.find(
                        (item) =>
                            item.title === file.name &&
                            item.type !== TYPE_FOLDER &&
                            item.parent_id === dropzone_item.id
                    )
                ) {
                    this.error_modal_shown = this.ALREADY_EXISTS_ERROR;
                    return;
                }
            }

            let should_display_fake_item = false;
            if (!is_uploading_in_subfolder) {
                should_display_fake_item = true;
            } else {
                should_display_fake_item = dropzone_item.is_expanded;
            }

            if (is_uploading_in_subfolder && !dropzone_item.is_expanded) {
                this.$store.commit("toggleCollapsedFolderHasUploadingContent", [
                    dropzone_item,
                    true,
                ]);
            }

            for (const file of files) {
                try {
                    await this.$store.dispatch("addNewUploadFile", [
                        file,
                        dropzone_item,
                        file.name,
                        "",
                        should_display_fake_item,
                    ]);
                } catch (error) {
                    this.error_modal_shown = this.CREATION_ERROR;
                    this.error_modal_reasons.push({ filename: file.name, message: error });
                }
            }
        },
        errorModalHasBeenClosed() {
            this.error_modal_shown = false;
            this.error_modal_reasons = [];
        },
        isDragNDropingOnAModal(event) {
            return Boolean(event.target.closest(".tlp-modal"));
        },
        clearHighlight() {
            const highlighted_items = document.querySelectorAll(`
                .document-tree-item-highlighted,
                .document-tree-item-hightlighted-forbidden,
                .quick-look-pane-highlighted,
                .quick-look-pane-highlighted-forbidden
            `);

            for (const element of highlighted_items) {
                element.classList.remove(
                    "document-tree-item-highlighted",
                    "document-folder-highlighted",
                    "document-file-highlighted",
                    "document-tree-item-hightlighted-forbidden",
                    "quick-look-pane-highlighted",
                    "quick-look-pane-highlighted-forbidden"
                );
            }

            this.is_dropzone_highlighted = false;
            this.highlighted_item_id = null;
        },
        highlightFolderDropZone(event) {
            this.clearHighlight();

            const target_drop_zones = [
                ".document-tree-item-folder",
                ".document-quick-look-folder-dropzone",
                ".document-quick-look-file-dropzone",
            ];

            if (!event.dataTransfer.items) {
                this.error_modal_shown = this.IE11_NOT_SUPPORTED;
                return;
            }

            if (event.dataTransfer.items.length === 1) {
                target_drop_zones.push(".document-tree-item-file");
            }

            const closest_row = event.target.closest(target_drop_zones);

            if (closest_row) {
                this.highlighted_item_id = parseInt(closest_row.dataset.itemId, 10);

                const item = this.getDropZoneItem();

                highlightItem(item, closest_row);
            } else {
                this.is_dropzone_highlighted = true;
            }
        },
        getDropZoneItem: function () {
            if (!this.highlighted_item_id) {
                return this.current_folder;
            }

            return this.folder_content.find((item) => item.id === this.highlighted_item_id);
        },
        async uploadNewFileVersion(event, dropzone_item) {
            const { lock_info, approval_table } = dropzone_item;
            const is_document_locked_by_current_user =
                lock_info === null ||
                (lock_info !== null && lock_info.locked_by.id === this.user_id);

            if (!is_document_locked_by_current_user) {
                this.error_modal_shown = this.EDITION_LOCKED;
                this.error_modal_reasons.push({
                    filename: dropzone_item.title,
                    lock_owner: lock_info.locked_by,
                });

                return;
            }

            const is_an_approval_table_blocking_update =
                approval_table !== null && !approval_table.has_been_approved;

            if (is_an_approval_table_blocking_update) {
                this.error_modal_shown = this.DOCUMENT_NEEDS_APPROVAL;
                this.error_modal_reasons.push({
                    filename: dropzone_item.title,
                    approval_table_owner: approval_table.table_owner,
                    approval_table_state: approval_table.approval_state,
                    item_id: dropzone_item.id,
                });

                return;
            }

            const files = event.dataTransfer.files;
            const file = files[0];

            const is_item_a_file = await this.isDroppedItemAFile(file);
            if (!is_item_a_file) {
                this.error_modal_shown = this.DROPPED_ITEM_IS_NOT_A_FILE;
                this.error_modal_reasons.push({ nb_dropped_files: 1 });

                return;
            }

            if (file.size > this.max_size_upload) {
                this.error_modal_shown = this.MAX_SIZE_ERROR;
                return;
            }

            try {
                await this.$store.dispatch("createNewFileVersion", [dropzone_item, file]);
            } catch (error) {
                this.error_modal_shown = this.CREATION_ERROR;
                this.error_modal_reasons.push({ filename: file.name, message: error });
            }
        },
        async isDroppedItemAFile(file) {
            const read_file_operation = new Promise((resolve) => {
                const reader = new FileReader();

                reader.onload = () => {
                    resolve(true);
                };

                reader.onerror = () => {
                    resolve(false);
                };

                try {
                    reader.readAsText(file);
                } catch (error) {
                    resolve(false);
                }
            });

            const is_a_file = await read_file_operation;

            return is_a_file;
        },
    },
};
</script>
