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
        <div class="document-header-actions" data-test="document-header-actions">
            <div class="tlp-dropdown" v-if="can_display_new_document_button">
                <folder-header-action v-bind:item="current_folder" />
                <new-item-modal />
                <new-folder-modal />
                <create-new-item-version-modal
                    v-bind:is="shown_new_version_modal"
                    v-bind:item="updated_item"
                    data-test="document-new-version-modal"
                />
                <update-metadata-modal
                    v-bind:is="shown_update_metadata_modal"
                    v-bind:item="updated_metadata"
                    data-test="document-update-metadata-modal"
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
            v-on:delete-modal-closed="hideDeleteItemModal"
            data-test="document-delete-item-modal"
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
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants.js";
import SearchBox from "./SearchBox.vue";
import FileUploadManager from "./FilesUploads/FilesUploadsManager.vue";
import NewItemModal from "./ModalNewItem/NewItemModal.vue";
import NewFolderModal from "./ModalNewItem/NewFolderModal.vue";
import FolderHeaderAction from "./FolderHeaderAction.vue";
import EventBus from "../../helpers/event-bus.js";

export default {
    name: "FolderHeader",
    components: {
        FolderHeaderAction,
        NewFolderModal,
        SearchBox,
        NewItemModal,
        FileUploadManager,
        "confirm-deletion-modal": () =>
            import(
                /* webpackChunkName: "document-confirm-item-deletion-modal" */
                "./ModalDeleteItem/ModalConfirmDeletion.vue"
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
                "./ModalCreateNewItemVersion/FileVersionChangelogModal.vue"
            ),
    },
    data() {
        return {
            shown_new_version_modal: "",
            shown_update_metadata_modal: "",
            updated_item: null,
            updated_metadata: null,
            item_to_delete: null,
            item_to_update_permissions: {},
            current_folder_size: null,
            folder_above_warning_threshold_props: null,
            file_changelog_properties: null,
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
        EventBus.$on("show-create-new-item-version-modal", this.showCreateNewItemVersionModal);
        EventBus.$on("show-confirm-item-deletion-modal", this.showDeleteItemModal);
        EventBus.$on("show-update-item-metadata-modal", this.showUpdateItemMetadataModal);
        EventBus.$on("show-update-permissions-modal", this.showUpdateItemPermissionsModal);
        EventBus.$on(
            "show-max-archive-size-threshold-exceeded-modal",
            this.showMaxArchiveSizeThresholdExceededErrorModal
        );
        EventBus.$on("show-archive-size-warning-modal", this.showArchiveSizeWarningModal);
        EventBus.$on("show-changelog-modal", this.showChangelogModal);
    },
    beforeDestroy() {
        EventBus.$off("show-create-new-item-version-modal", this.showCreateNewItemVersionModal);
        EventBus.$off("show-confirm-item-deletion-modal", this.showDeleteItemModal);
        EventBus.$off("show-update-item-metadata-modal", this.showUpdateItemMetadataModal);
        EventBus.$off("show-update-permissions-modal", this.showUpdateItemPermissionsModal);
        EventBus.$off(
            "show-max-archive-size-threshold-exceeded-modal",
            this.showMaxArchiveSizeThresholdExceededErrorModal
        );
        EventBus.$off("show-archive-size-warning-modal", this.showArchiveSizeWarningModal);
        EventBus.$off("show-changelog-modal", this.showChangelogModal);
    },
    methods: {
        showCreateNewItemVersionModal(event) {
            this.updated_item = event.detail.current_item;

            switch (this.updated_item.type) {
                case TYPE_FILE:
                    this.shown_new_version_modal = () =>
                        import(
                            /* webpackChunkName: "document-new-file-version-modal" */ "./ModalCreateNewItemVersion/CreateNewVersionFileModal.vue"
                        );
                    break;
                case TYPE_EMBEDDED:
                    this.shown_new_version_modal = () =>
                        import(
                            /* webpackChunkName: "document-new-embedded-version-file-modal" */ "./ModalCreateNewItemVersion/CreateNewVersionEmbeddedFileModal.vue"
                        );
                    break;
                case TYPE_WIKI:
                    this.shown_new_version_modal = () =>
                        import(
                            /* webpackChunkName: "document-new-wiki-version-modal" */ "./ModalCreateNewItemVersion/CreateNewVersionWikiModal.vue"
                        );
                    break;
                case TYPE_LINK:
                    this.shown_new_version_modal = () =>
                        import(
                            /* webpackChunkName: "document-new-link-version-modal" */ "./ModalCreateNewItemVersion/CreateNewVersionLinkModal.vue"
                        );
                    break;
                case TYPE_EMPTY:
                    this.shown_new_version_modal = () =>
                        import(
                            /* webpackChunkName: "document-new-empty-version-modal" */ "./ModalCreateNewItemVersion/CreateNewVersionEmptyModal.vue"
                        );
                    break;
                default: //nothing
            }
        },
        showChangelogModal(event) {
            this.file_changelog_properties = event.detail;
        },
        showDeleteItemModal(event) {
            this.item_to_delete = event.detail.current_item;
        },
        showUpdateItemMetadataModal(event) {
            this.updated_metadata = event.detail.current_item;
            if (!this.isItemAFolder(this.updated_metadata)) {
                this.shown_update_metadata_modal = () =>
                    import(
                        /* webpackChunkName: "update-metadata-modal" */ "./ModalUpdateMetadata/UpdateMetadataModal.vue"
                    );
            } else {
                this.shown_update_metadata_modal = () =>
                    import(
                        /* webpackChunkName: "update-folder-metadata-modal" */ "./ModalUpdateMetadata/UpdateFolderMetadataModal.vue"
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
        hideDeleteItemModal() {
            this.item_to_delete = null;
        },
        hideDownloadFolderModals() {
            this.current_folder_size = null;
            this.folder_above_warning_threshold_props = null;
        },
        showUpdateItemPermissionsModal(event) {
            this.item_to_update_permissions = event.detail.current_item;
        },
        isItemAFolder(item) {
            return item.type === TYPE_FOLDER;
        },
    },
};
</script>
