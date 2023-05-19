<!--
  - Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -
  -->

<template>
    <div class="document-header">
        <h1 class="document-header-title">
            <span v-bind:class="title_class" data-test="document-folder-header-title">
                {{ folder_title }}
            </span>
        </h1>
        <div
            class="document-header-actions"
            data-test="document-header-actions"
            data-shortcut-header-actions
        >
            <div class="tlp-dropdown" v-if="can_display_new_document_button">
                <folder-header-action v-bind:item="current_folder" />
                <new-item-modal />
                <new-folder-modal />
                <create-new-item-version-modal
                    v-bind:is="shown_new_version_modal"
                    v-bind:item="updated_item"
                    v-bind:type="updated_empty_new_type"
                    data-test="document-new-version-modal"
                />
                <update-properties-modal
                    v-bind:is="shown_update_properties_modal"
                    v-bind:item="updated_properties"
                    data-test="document-update-properties-modal"
                />
            </div>
            <div class="document-header-spacer"></div>
            <file-upload-manager />
            <search-box
                v-if="can_display_search_box"
                data-test="document-folder-harder-search-box"
            />
        </div>
        <confirm-deletion-modal
            v-if="item_to_delete"
            v-bind:item="item_to_delete"
            data-test="document-delete-item-modal"
            v-on:delete-modal-closed="hideDeleteItemModal"
        />
        <permissions-update-modal
            v-bind:item="item_to_update_permissions"
            data-test="document-permissions-item-modal"
            v-if="Object.keys(item_to_update_permissions).length > 0"
        />
        <download-folder-size-threshold-exceeded-modal
            v-if="current_folder_size !== null"
            v-bind:size="current_folder_size"
            v-on:download-as-zip-modal-closed="hideDownloadFolderModals()"
            data-test="document-folder-size-threshold-exceeded"
        />
        <download-folder-size-warning-modal
            v-if="folder_above_warning_threshold_props"
            v-bind:size="folder_above_warning_threshold_props.folder_size"
            v-bind:folder-href="folder_above_warning_threshold_props.folder_href"
            v-bind:should-warn-osx-user="folder_above_warning_threshold_props.should_warn_osx_user"
            v-on:download-folder-as-zip-modal-closed="hideDownloadFolderModals()"
            data-test="document-folder-size-warning-modal"
        />
        <file-changelog-modal
            v-if="file_changelog_properties"
            v-on:close-changelog-modal="hideChangelogModal()"
            v-bind:updated-file="file_changelog_properties.updated_file"
            v-bind:dropped-file="file_changelog_properties.dropped_file"
            data-test="file-changelog-modal"
        />
        <file-creation-modal
            v-if="file_creation_properties"
            v-on:close-file-creation-modal="hideFileCreationModal()"
            v-bind:dropped-file="file_creation_properties.dropped_file"
            v-bind:parent="file_creation_properties.parent"
        />
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import { TYPE_EMBEDDED, TYPE_FILE, TYPE_LINK, TYPE_WIKI } from "../../constants";
import SearchBox from "./SearchBox.vue";
import FileUploadManager from "./FilesUploads/FilesUploadsManager.vue";
import NewItemModal from "./DropDown/NewDocument/NewItemModal.vue";
import NewFolderModal from "./DropDown/NewDocument/NewFolderModal.vue";
import FolderHeaderAction from "./FolderHeaderAction.vue";
import { isFolder } from "../../helpers/type-check-helper";
import emitter from "../../helpers/emitter";
import FileCreationModal from "./DropDown/NewDocument/FileCreationModal.vue";

export default {
    name: "FolderHeader",
    components: {
        FileCreationModal,
        FolderHeaderAction,
        NewFolderModal,
        SearchBox,
        NewItemModal,
        FileUploadManager,
        "confirm-deletion-modal": () =>
            import(
                /* webpackChunkName: "document-confirm-item-deletion-modal" */
                "./DropDown/Delete/ModalConfirmDeletion.vue"
            ),
        "permissions-update-modal": () =>
            import(
                /* webpackChunkName: "document-permissions-update-modal" */ "./Permissions/PermissionsUpdateModal.vue"
            ),
        "download-folder-size-threshold-exceeded-modal": () =>
            import(
                /* webpackChunkName: "document-download-folder-size-exceeded-modal" */
                "./DropDown/DownloadFolderAsZip/ModalMaxArchiveSizeThresholdExceeded.vue"
            ),
        "download-folder-size-warning-modal": () =>
            import(
                /* webpackChunkName: "document-download-folder-size-warning-modal" */
                "./DropDown/DownloadFolderAsZip/ModalArchiveSizeWarning.vue"
            ),
        "file-changelog-modal": () =>
            import(
                /* webpackChunkName: "file-changelog-modal" */
                "./DropDown/NewVersion/FileVersionChangelogModal.vue"
            ),
        "file-creation-modal": () =>
            import(
                /* webpackChunkName: "file-creation-modal" */
                "./DropDown/NewDocument/FileCreationModal.vue"
            ),
    },
    data() {
        return {
            shown_new_version_modal: "",
            shown_update_properties_modal: "",
            updated_item: null,
            updated_properties: null,
            item_to_delete: null,
            item_to_update_permissions: {},
            current_folder_size: null,
            folder_above_warning_threshold_props: null,
            file_changelog_properties: null,
            file_creation_properties: null,
            updated_empty_new_type: null,
        };
    },
    computed: {
        ...mapState(["is_loading_ascendant_hierarchy", "current_folder"]),
        ...mapGetters(["current_folder_title", "is_folder_empty"]),
        title_class() {
            return this.is_loading_ascendant_hierarchy
                ? "tlp-skeleton-text document-folder-title-loading"
                : "";
        },
        folder_title() {
            return this.is_loading_ascendant_hierarchy ? "" : this.current_folder_title;
        },
        can_display_search_box() {
            return this.current_folder && !this.is_folder_empty;
        },
        can_display_new_document_button() {
            return this.current_folder;
        },
    },
    created() {
        emitter.on("deleteItem", this.showDeleteItemModal);
        emitter.on("show-create-new-item-version-modal", this.showCreateNewItemVersionModal);
        emitter.on(
            "show-create-new-version-modal-for-empty",
            this.showCreateNewVersionModalForEmpty
        );
        emitter.on("show-update-item-properties-modal", this.showUpdateItemPropertiesModal);
        emitter.on("show-update-permissions-modal", this.showUpdateItemPermissionsModal);
        emitter.on(
            "show-max-archive-size-threshold-exceeded-modal",
            this.showMaxArchiveSizeThresholdExceededErrorModal
        );
        emitter.on("show-archive-size-warning-modal", this.showArchiveSizeWarningModal);
        emitter.on("show-changelog-modal", this.showChangelogModal);
        emitter.on("show-file-creation-modal", this.showFileCreationModal);
    },
    beforeUnmount() {
        emitter.off("deleteItem", this.showDeleteItemModal);
        emitter.off("show-create-new-item-version-modal", this.showCreateNewItemVersionModal);
        emitter.off(
            "show-create-new-version-modal-for-empty",
            this.showCreateNewVersionModalForEmpty
        );
        emitter.off("show-update-item-properties-modal", this.showUpdateItemPropertiesModal);
        emitter.off("show-update-permissions-modal", this.showUpdateItemPermissionsModal);
        emitter.off(
            "show-max-archive-size-threshold-exceeded-modal",
            this.showMaxArchiveSizeThresholdExceededErrorModal
        );
        emitter.off("show-archive-size-warning-modal", this.showArchiveSizeWarningModal);
        emitter.off("show-changelog-modal", this.showChangelogModal);
        emitter.off("show-file-creation-modal", this.showFileCreationModal);
    },
    methods: {
        showCreateNewVersionModalForEmpty(event) {
            this.updated_item = event.item;
            this.updated_empty_new_type = event.type;
            this.shown_new_version_modal = () =>
                import(
                    /* webpackChunkName: "document-new-empty-version-modal" */ "./DropDown/NewVersion/CreateNewVersionEmptyModal.vue"
                );
        },
        showCreateNewItemVersionModal(event) {
            this.updated_item = event.detail.current_item;

            switch (this.updated_item.type) {
                case TYPE_FILE:
                    this.shown_new_version_modal = () =>
                        import(
                            /* webpackChunkName: "document-new-file-version-modal" */ "./DropDown/NewVersion/CreateNewVersionFileModal.vue"
                        );
                    break;
                case TYPE_EMBEDDED:
                    this.shown_new_version_modal = () =>
                        import(
                            /* webpackChunkName: "document-new-embedded-version-file-modal" */ "./DropDown/NewVersion/CreateNewVersionEmbeddedFileModal.vue"
                        );
                    break;
                case TYPE_WIKI:
                    this.shown_new_version_modal = () =>
                        import(
                            /* webpackChunkName: "document-new-wiki-version-modal" */ "./DropDown/NewVersion/CreateNewVersionWikiModal.vue"
                        );
                    break;
                case TYPE_LINK:
                    this.shown_new_version_modal = () =>
                        import(
                            /* webpackChunkName: "document-new-link-version-modal" */ "./DropDown/NewVersion/CreateNewVersionLinkModal.vue"
                        );
                    break;
                default: //nothing
            }
        },
        showChangelogModal(event) {
            this.file_changelog_properties = event.detail;
        },

        showFileCreationModal(event) {
            this.file_creation_properties = event.detail;
        },
        showUpdateItemPropertiesModal(event) {
            if (!event.detail.current_item) {
                return;
            }
            this.updated_properties = event.detail.current_item;
            if (!this.isItemAFolder(this.updated_properties)) {
                this.shown_update_properties_modal = () =>
                    import(
                        /* webpackChunkName: "update-properties-modal" */ "./DropDown/UpdateProperties/UpdatePropertiesModal.vue"
                    );
            } else {
                this.shown_update_properties_modal = () =>
                    import(
                        /* webpackChunkName: "update-folder-properties-modal" */ "./DropDown/UpdateProperties/UpdateFolderPropertiesModal.vue"
                    );
            }
        },
        showMaxArchiveSizeThresholdExceededErrorModal(event) {
            this.current_folder_size = event.detail.current_folder_size;
        },
        showArchiveSizeWarningModal(event) {
            this.folder_above_warning_threshold_props = {
                folder_size: event.detail.current_folder_size,
                folder_href: event.detail.folder_href,
                should_warn_osx_user: event.detail.should_warn_osx_user,
            };
        },
        hideChangelogModal() {
            this.file_changelog_properties = null;
        },
        hideFileCreationModal() {
            this.file_creation_properties = null;
        },
        hideDownloadFolderModals() {
            this.current_folder_size = null;
            this.folder_above_warning_threshold_props = null;
        },
        showUpdateItemPermissionsModal(event) {
            this.item_to_update_permissions = event.detail.current_item;
        },
        isItemAFolder(item) {
            return isFolder(item);
        },
        showDeleteItemModal(event) {
            this.item_to_delete = event.item;
        },
        hideDeleteItemModal() {
            this.item_to_delete = null;
        },
    },
};
</script>
