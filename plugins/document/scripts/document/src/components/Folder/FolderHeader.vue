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
                <component
                    v-if="shown_new_version_modal !== null"
                    v-bind:is="shown_new_version_modal"
                    v-bind:item="updated_item"
                    v-bind:type="updated_empty_new_type"
                    data-test="document-new-version-modal"
                />
                <component
                    v-if="shown_update_properties_modal !== null"
                    v-bind:is="shown_update_properties_modal"
                    v-bind:item="updated_item"
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
        <modal-confirm-deletion
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
        <modal-max-archive-size-threshold-exceeded
            v-if="current_folder_size !== null"
            v-bind:size="current_folder_size"
            v-on:download-as-zip-modal-closed="hideDownloadFolderModals()"
            data-test="document-folder-size-threshold-exceeded"
        />
        <modal-archive-size-warning
            v-if="folder_above_warning_threshold_props"
            v-bind:size="folder_above_warning_threshold_props.folder_size"
            v-bind:folder-href="folder_above_warning_threshold_props.folder_href"
            v-bind:should-warn-osx-user="folder_above_warning_threshold_props.should_warn_osx_user"
            v-on:download-folder-as-zip-modal-closed="hideDownloadFolderModals()"
            data-test="document-folder-size-warning-modal"
        />
        <file-version-changelog-modal
            v-if="file_changelog_properties"
            v-on:close-changelog-modal="hideChangelogModal()"
            v-bind:updated_file="file_changelog_properties.updated_file"
            v-bind:dropped_file="file_changelog_properties.dropped_file"
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

<script setup lang="ts">
import { TYPE_EMBEDDED, TYPE_FILE, TYPE_LINK, TYPE_WIKI } from "../../constants";
import SearchBox from "./SearchBox.vue";
import FileUploadManager from "./FilesUploads/FilesUploadsManager.vue";
import NewItemModal from "./DropDown/NewDocument/NewItemModal.vue";
import NewFolderModal from "./DropDown/NewDocument/NewFolderModal.vue";
import FolderHeaderAction from "./FolderHeaderAction.vue";
import { isFolder } from "../../helpers/type-check-helper";
import emitter from "../../helpers/emitter";
import FileCreationModal from "./DropDown/NewDocument/FileCreationModal.vue";
import type { Component } from "vue";
import { computed, defineAsyncComponent, onBeforeUnmount, onMounted, ref } from "vue";
import type {
    ArchiveSizeWarningModalEvent,
    DeleteItemEvent,
    MaxArchiveSizeThresholdExceededEvent,
    NewVersionEvent,
    ShowChangelogModalEvent,
    ShowChangelogModalEventDetail,
    ShowFileCreationModalEvent,
    ShowFileCreationModalEventDetail,
    UpdatePermissionsEvent,
    UpdatePropertiesEvent,
} from "../../helpers/emitter";
import { useGetters, useState } from "vuex-composition-helpers";
import type { Empty, Item, ItemType, RootState } from "../../type";
import type { RootGetter } from "../../store/getters";

const ModalConfirmDeletion = defineAsyncComponent(
    () => import("./DropDown/Delete/ModalConfirmDeletion.vue"),
);
const PermissionsUpdateModal = defineAsyncComponent(
    () => import("./Permissions/PermissionsUpdateModal.vue"),
);
const ModalMaxArchiveSizeThresholdExceeded = defineAsyncComponent(
    () => import("./DropDown/DownloadFolderAsZip/ModalMaxArchiveSizeThresholdExceeded.vue"),
);
const ModalArchiveSizeWarning = defineAsyncComponent(
    () => import("./DropDown/DownloadFolderAsZip/ModalArchiveSizeWarning.vue"),
);
const FileVersionChangelogModal = defineAsyncComponent(
    () => import("./DropDown/NewVersion/FileVersionChangelogModal.vue"),
);

const shown_new_version_modal = ref<Component | null>(null);
const shown_update_properties_modal = ref<Component | null>(null);
const updated_item = ref<Item | null>(null);
const item_to_delete = ref<Item | null>(null);
const item_to_update_permissions = ref({});
const current_folder_size = ref<number | null>(null);
const folder_above_warning_threshold_props = ref<{
    folder_size: number;
    folder_href: string;
    should_warn_osx_user: boolean;
} | null>(null);
const file_changelog_properties = ref<ShowChangelogModalEventDetail | null>(null);
const file_creation_properties = ref<ShowFileCreationModalEventDetail | null>(null);
const updated_empty_new_type = ref<ItemType | null>(null);

const { is_loading_ascendant_hierarchy, current_folder } = useState<
    Pick<RootState, "is_loading_ascendant_hierarchy" | "current_folder">
>(["is_loading_ascendant_hierarchy", "current_folder"]);
const { current_folder_title, is_folder_empty } = useGetters<
    Pick<RootGetter, "current_folder_title" | "is_folder_empty">
>(["current_folder_title", "is_folder_empty"]);

const title_class = computed(() =>
    is_loading_ascendant_hierarchy.value ? "tlp-skeleton-text document-folder-title-loading" : "",
);
const folder_title = computed(() =>
    is_loading_ascendant_hierarchy.value ? "" : current_folder_title.value,
);
const can_display_search_box = computed(
    () => current_folder.value !== null && !is_folder_empty.value,
);
const can_display_new_document_button = computed(() => current_folder.value !== null);

onMounted(() => {
    emitter.on("deleteItem", showDeleteItemModal);
    emitter.on("show-create-new-item-version-modal", showCreateNewItemVersionModal);
    emitter.on("show-create-new-version-modal-for-empty", showCreateNewVersionModalForEmpty);
    emitter.on("show-update-item-properties-modal", showUpdateItemPropertiesModal);
    emitter.on("show-update-permissions-modal", showUpdateItemPermissionsModal);
    emitter.on(
        "show-max-archive-size-threshold-exceeded-modal",
        showMaxArchiveSizeThresholdExceededErrorModal,
    );
    emitter.on("show-archive-size-warning-modal", showArchiveSizeWarningModal);
    emitter.on("show-changelog-modal", showChangelogModal);
    emitter.on("show-file-creation-modal", showFileCreationModal);
});

onBeforeUnmount(() => {
    emitter.off("deleteItem", showDeleteItemModal);
    emitter.off("show-create-new-item-version-modal", showCreateNewItemVersionModal);
    emitter.off("show-create-new-version-modal-for-empty", showCreateNewVersionModalForEmpty);
    emitter.off("show-update-item-properties-modal", showUpdateItemPropertiesModal);
    emitter.off("show-update-permissions-modal", showUpdateItemPermissionsModal);
    emitter.off(
        "show-max-archive-size-threshold-exceeded-modal",
        showMaxArchiveSizeThresholdExceededErrorModal,
    );
    emitter.off("show-archive-size-warning-modal", showArchiveSizeWarningModal);
    emitter.off("show-changelog-modal", showChangelogModal);
    emitter.off("show-file-creation-modal", showFileCreationModal);
});

function showCreateNewVersionModalForEmpty(event: { item: Empty; type: ItemType }): void {
    updated_item.value = event.item;
    updated_empty_new_type.value = event.type;
    shown_new_version_modal.value = defineAsyncComponent(
        () => import("./DropDown/NewVersion/CreateNewVersionEmptyModal.vue"),
    );
}

function showCreateNewItemVersionModal(event: NewVersionEvent): void {
    updated_item.value = event.detail.current_item;

    switch (updated_item.value.type) {
        case TYPE_FILE:
            shown_new_version_modal.value = defineAsyncComponent(
                () => import("./DropDown/NewVersion/CreateNewVersionFileModal.vue"),
            );
            break;
        case TYPE_EMBEDDED:
            shown_new_version_modal.value = defineAsyncComponent(
                () => import("./DropDown/NewVersion/CreateNewVersionEmbeddedFileModal.vue"),
            );
            break;
        case TYPE_WIKI:
            shown_new_version_modal.value = defineAsyncComponent(
                () => import("./DropDown/NewVersion/CreateNewVersionWikiModal.vue"),
            );
            break;
        case TYPE_LINK:
            shown_new_version_modal.value = defineAsyncComponent(
                () => import("./DropDown/NewVersion/CreateNewVersionLinkModal.vue"),
            );
            break;
        default:
    }
}

function showChangelogModal(event: ShowChangelogModalEvent): void {
    file_changelog_properties.value = event.detail;
}

function showFileCreationModal(event: ShowFileCreationModalEvent): void {
    file_creation_properties.value = event.detail;
}

function showUpdateItemPropertiesModal(event: UpdatePropertiesEvent): void {
    if (!event.detail.current_item) {
        return;
    }
    updated_item.value = event.detail.current_item;
    if (!isFolder(updated_item.value)) {
        shown_update_properties_modal.value = defineAsyncComponent(
            () => import("./DropDown/UpdateProperties/UpdatePropertiesModal.vue"),
        );
    } else {
        shown_update_properties_modal.value = defineAsyncComponent(
            () => import("./DropDown/UpdateProperties/UpdateFolderPropertiesModal.vue"),
        );
    }
}

function showMaxArchiveSizeThresholdExceededErrorModal(
    event: MaxArchiveSizeThresholdExceededEvent,
): void {
    current_folder_size.value = event.detail.current_folder_size;
}

function showArchiveSizeWarningModal(event: ArchiveSizeWarningModalEvent): void {
    folder_above_warning_threshold_props.value = {
        folder_size: event.detail.current_folder_size,
        folder_href: event.detail.folder_href,
        should_warn_osx_user: event.detail.should_warn_osx_user,
    };
}

function hideChangelogModal(): void {
    file_changelog_properties.value = null;
}

function hideFileCreationModal(): void {
    file_creation_properties.value = null;
}

function hideDownloadFolderModals(): void {
    current_folder_size.value = null;
    folder_above_warning_threshold_props.value = null;
}

function showUpdateItemPermissionsModal(event: UpdatePermissionsEvent): void {
    item_to_update_permissions.value = event.detail.current_item;
}

function showDeleteItemModal(event: DeleteItemEvent): void {
    item_to_delete.value = event.item;
}

function hideDeleteItemModal(): void {
    item_to_delete.value = null;
}
</script>
