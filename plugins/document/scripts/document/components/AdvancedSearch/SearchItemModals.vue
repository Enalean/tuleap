<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
  -
  -->

<template>
    <div>
        <new-item-modal />
        <new-folder-modal />
        <create-new-item-version-modal
            v-bind:is="shown_new_version_modal"
            v-bind:item="updated_item"
            data-test="document-new-version-modal"
        />
        <update-properties-modal
            v-bind:is="shown_update_properties_modal"
            v-bind:item="updated_properties"
            data-test="document-update-properties-modal"
        />
        <confirm-deletion-modal
            v-if="item_to_delete"
            v-bind:item="item_to_delete"
            data-test="document-delete-item-modal"
            v-on:delete-modal-closed="hideDeleteItemModal"
        />
        <permissions-update-modal
            v-bind:item="item_to_update_permissions"
            data-test="document-permissions-item-modal"
            v-if="item_to_update_permissions"
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
        <ongoing-upload-modal
            v-if="can_display_ongoing_upload_modal"
            v-on:close="ongoingUploadModalHasBeenClosed"
        />
    </div>
</template>

<script setup lang="ts">
import type {
    ArchiveSizeWarningModalEvent,
    DeleteItemEvent,
    MaxArchiveSizeThresholdExceededEvent,
    NewVersionEvent,
    UpdatePermissionsEvent,
    UpdatePropertiesEvent,
} from "../../helpers/emitter";
import emitter from "../../helpers/emitter";
// eslint-disable-next-line import/no-duplicates
import { computed, defineAsyncComponent, onMounted, onUnmounted, ref, shallowRef } from "vue";
import type { Item } from "../../type";
import { isFolder } from "../../helpers/type-check-helper";
import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_FILE, TYPE_LINK, TYPE_WIKI } from "../../constants";
import OngoingUploadModal from "./OngoingUploadModal.vue";

const item_to_delete = ref<Item | null>(null);

function showDeleteItemModal(event: DeleteItemEvent): void {
    item_to_delete.value = event.item;
}

function hideDeleteItemModal(): void {
    item_to_delete.value = null;
}

const item_to_update_permissions = ref<Item | null>(null);

function showUpdateItemPermissionsModal(event: UpdatePermissionsEvent): void {
    item_to_update_permissions.value = event.detail.current_item;
}

const updated_properties = ref<Item | null>(null);
const shown_update_properties_modal = shallowRef<undefined | unknown>(undefined);

function showUpdateItemPropertiesModal(event: UpdatePropertiesEvent): void {
    updated_properties.value = event.detail.current_item;
    if (isFolder(updated_properties.value)) {
        shown_update_properties_modal.value = defineAsyncComponent(
            () =>
                import(
                    /* webpackChunkName: "update-folder-properties-modal" */ "../Folder/DropDown/UpdateProperties/UpdateFolderPropertiesModal.vue"
                ),
        );
    } else {
        shown_update_properties_modal.value = defineAsyncComponent(
            () =>
                import(
                    /* webpackChunkName: "update-properties-modal" */ "../Folder/DropDown/UpdateProperties/UpdatePropertiesModal.vue"
                ),
        );
    }
}

const updated_item = ref<Item | null>(null);
const shown_new_version_modal = shallowRef<undefined | unknown>(undefined);

function showCreateNewItemVersionModal(event: NewVersionEvent): void {
    updated_item.value = event.detail.current_item;

    switch (updated_item.value.type) {
        case TYPE_FILE:
            shown_new_version_modal.value = defineAsyncComponent(
                () =>
                    import(
                        /* webpackChunkName: "document-new-file-version-modal" */ "../Folder/DropDown/NewVersion/CreateNewVersionFileModal.vue"
                    ),
            );
            break;
        case TYPE_EMBEDDED:
            shown_new_version_modal.value = defineAsyncComponent(
                () =>
                    import(
                        /* webpackChunkName: "document-new-embedded-version-file-modal" */ "../Folder/DropDown/NewVersion/CreateNewVersionEmbeddedFileModal.vue"
                    ),
            );
            break;
        case TYPE_WIKI:
            shown_new_version_modal.value = defineAsyncComponent(
                () =>
                    import(
                        /* webpackChunkName: "document-new-wiki-version-modal" */ "../Folder/DropDown/NewVersion/CreateNewVersionWikiModal.vue"
                    ),
            );
            break;
        case TYPE_LINK:
            shown_new_version_modal.value = defineAsyncComponent(
                () =>
                    import(
                        /* webpackChunkName: "document-new-link-version-modal" */ "../Folder/DropDown/NewVersion/CreateNewVersionLinkModal.vue"
                    ),
            );
            break;
        case TYPE_EMPTY:
            shown_new_version_modal.value = defineAsyncComponent(
                () =>
                    import(
                        /* webpackChunkName: "document-new-empty-version-modal" */ "../Folder/DropDown/NewVersion/CreateNewVersionEmptyModal.vue"
                    ),
            );
            break;
        default: //nothing
    }
}

const folder_above_warning_threshold_props = ref<{
    folder_size: number;
    folder_href: string;
    should_warn_osx_user: boolean;
} | null>(null);

function showArchiveSizeWarningModal(event: ArchiveSizeWarningModalEvent): void {
    folder_above_warning_threshold_props.value = {
        folder_size: event.detail.current_folder_size,
        folder_href: event.detail.folder_href,
        should_warn_osx_user: event.detail.should_warn_osx_user,
    };
}

const current_folder_size = ref<number | null>(null);

function showMaxArchiveSizeThresholdExceededErrorModal(
    event: MaxArchiveSizeThresholdExceededEvent,
): void {
    current_folder_size.value = event.detail.current_folder_size;
}

function hideDownloadFolderModals(): void {
    current_folder_size.value = null;
    folder_above_warning_threshold_props.value = null;
}

const should_display_ongoing_upload_modal = ref(false);

function itemIsBeingUploaded(): void {
    should_display_ongoing_upload_modal.value = true;
}

function ongoingUploadModalHasBeenClosed(): void {
    should_display_ongoing_upload_modal.value = false;
}

const can_display_ongoing_upload_modal = computed((): boolean => {
    return (
        should_display_ongoing_upload_modal.value &&
        should_display_ongoing_upload_modal.value !== false
    );
});

onMounted(() => {
    emitter.on("deleteItem", showDeleteItemModal);
    emitter.on("show-create-new-item-version-modal", showCreateNewItemVersionModal);
    emitter.on("show-update-item-properties-modal", showUpdateItemPropertiesModal);
    emitter.on("show-update-permissions-modal", showUpdateItemPermissionsModal);
    emitter.on(
        "show-max-archive-size-threshold-exceeded-modal",
        showMaxArchiveSizeThresholdExceededErrorModal,
    );
    emitter.on("show-archive-size-warning-modal", showArchiveSizeWarningModal);
    emitter.on("item-is-being-uploaded", itemIsBeingUploaded);
});

onUnmounted(() => {
    emitter.off("deleteItem", showDeleteItemModal);
    emitter.off("show-create-new-item-version-modal", showCreateNewItemVersionModal);
    emitter.off("show-update-item-properties-modal", showUpdateItemPropertiesModal);
    emitter.off("show-update-permissions-modal", showUpdateItemPermissionsModal);
    emitter.off(
        "show-max-archive-size-threshold-exceeded-modal",
        showMaxArchiveSizeThresholdExceededErrorModal,
    );
    emitter.off("show-archive-size-warning-modal", showArchiveSizeWarningModal);
    emitter.off("item-is-being-uploaded", itemIsBeingUploaded);
});
</script>

<script lang="ts">
// eslint-disable-next-line import/no-duplicates
import { defineComponent } from "vue";

export default defineComponent({
    components: {
        "confirm-deletion-modal": () =>
            import(
                /* webpackChunkName: "document-confirm-item-deletion-modal" */
                "../Folder/DropDown/Delete/ModalConfirmDeletion.vue"
            ),
        "permissions-update-modal": () =>
            import(
                /* webpackChunkName: "document-permissions-update-modal" */ "../Folder/Permissions/PermissionsUpdateModal.vue"
            ),
        "download-folder-size-threshold-exceeded-modal": () =>
            import(
                /* webpackChunkName: "document-download-folder-size-exceeded-modal" */
                "../Folder/DropDown/DownloadFolderAsZip/ModalMaxArchiveSizeThresholdExceeded.vue"
            ),
        "download-folder-size-warning-modal": () =>
            import(
                /* webpackChunkName: "document-download-folder-size-warning-modal" */
                "../Folder/DropDown/DownloadFolderAsZip/ModalArchiveSizeWarning.vue"
            ),
        "file-changelog-modal": () =>
            import(
                /* webpackChunkName: "file-changelog-modal" */
                "../Folder/DropDown/NewVersion/FileVersionChangelogModal.vue"
            ),
        "new-item-modal": () =>
            import(
                /* webpackChunkName: "new-item-modal" */ "../Folder/DropDown/NewDocument/NewItemModal.vue"
            ),
        "new-folder-modal": () =>
            import(
                /* webpackChunkName: "new-folder-modal" */ "../Folder/DropDown/NewDocument/NewFolderModal.vue"
            ),
    },
});
</script>
