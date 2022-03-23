<!--
  - Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="embedded-document-container">
        <div class="document-header tlp-framed-horizontally">
            <document-title-lock-info
                v-bind:item="currently_previewed_item"
                v-bind:is-displaying-in-header="true"
            />

            <h1 class="document-header-title">{{ currently_previewed_item.title }}</h1>

            <actions-header v-bind:item="currently_previewed_item" />

            <approval-badge
                v-bind:item="currently_previewed_item"
                v-bind:is-in-folder-content-row="false"
            />

            <embedded-file-edition-switcher v-bind:is-in-large-view="is_embedded_in_large_view" />
        </div>

        <section
            class="tlp-pane embedded-document"
            v-bind:class="{ narrow: !is_embedded_in_large_view }"
            data-test="display-embedded-content"
        >
            <div class="tlp-pane-container">
                <section class="tlp-pane-section" v-dompurify-html="embedded_content"></section>
            </div>
        </section>

        <create-new-embedded-file-version-modal
            v-if="is_modal_shown"
            v-bind:item="currently_previewed_item"
            v-on:hidden="hideModal()"
        />
        <confirm-deletion-modal
            v-if="show_confirm_deletion_modal"
            v-bind:item="currently_previewed_item"
            v-bind:should-redirect-to-parent-after-deletion="true"
            v-on:delete-modal-closed="hideDeleteItemModal"
        />

        <update-properties-modal
            v-if="show_update_properties_modal"
            v-bind:item="currently_previewed_item"
            v-on:update-properties-modal-closed="hideUpdatePropertiesModal"
        />
        <permissions-update-modal
            v-if="show_update_permissions_modal"
            v-bind:item="currently_previewed_item"
        />
    </div>
</template>

<script setup lang="ts">
import emitter from "../../helpers/emitter";
import { computed, onBeforeMount, onUnmounted, ref } from "@vue/composition-api";
import type { PreferenciesState } from "../../store/preferencies/preferencies-default-state";
import { useNamespacedState, useState } from "vuex-composition-helpers";
import type { Embedded, Item, RootState } from "../../type";

const is_modal_shown = ref(false);
const show_confirm_deletion_modal = ref(false);
const show_update_properties_modal = ref(false);
const show_update_permissions_modal = ref(false);

const { currently_previewed_item } = useState<RootState>(["currently_previewed_item"]);
const { is_embedded_in_large_view } = useNamespacedState<PreferenciesState>("preferencies", [
    "is_embedded_in_large_view",
]);

const embedded_content = computed((): string => {
    const item = currently_previewed_item.value;
    if (!item) {
        return "";
    }

    if (!isEmbedded(item)) {
        return "";
    }

    if (!item.embedded_file_properties) {
        return "";
    }

    if (!item.embedded_file_properties.content) {
        return "";
    }

    return item.embedded_file_properties.content;
});

function isEmbedded(item: Item): item is Embedded {
    return "embedded_file_properties" in item;
}

onBeforeMount(() => {
    emitter.on("deleteItem", showDeleteItemModal);
    emitter.on("show-create-new-item-version-modal", showCreateNewItemVersionModal);
    emitter.on("show-update-item-properties-modal", showUpdatePropertiesModal);
    emitter.on("show-update-permissions-modal", showUpdateItemPermissionsModal);
});

onUnmounted(() => {
    emitter.off("deleteItem", showDeleteItemModal);
    emitter.off("show-create-new-item-version-modal", showCreateNewItemVersionModal);
    emitter.off("show-update-item-properties-modal", showUpdatePropertiesModal);
    emitter.off("show-update-permissions-modal", showUpdateItemPermissionsModal);
});

function showCreateNewItemVersionModal(): void {
    is_modal_shown.value = true;
}
function hideModal(): void {
    is_modal_shown.value = false;
}
function showUpdatePropertiesModal(): void {
    show_update_properties_modal.value = true;
}
function hideUpdatePropertiesModal(): void {
    show_update_properties_modal.value = false;
}
function showUpdateItemPermissionsModal(): void {
    show_update_permissions_modal.value = true;
}
function showDeleteItemModal(): void {
    show_confirm_deletion_modal.value = true;
}
function hideDeleteItemModal(): void {
    show_confirm_deletion_modal.value = false;
}
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";
import UpdatePropertiesModal from "../Folder/DropDown/UpdateProperties/UpdatePropertiesModal.vue";
import EmbeddedFileEditionSwitcher from "./EmbeddedFileEditionSwitcher.vue";
import ApprovalBadge from "../Folder/ApprovalTables/ApprovalBadge.vue";
import DocumentTitleLockInfo from "../Folder/LockInfo/DocumentTitleLockInfo.vue";
import ActionsHeader from "./ActionsHeader.vue";

export default defineComponent({
    components: {
        UpdatePropertiesModal,
        EmbeddedFileEditionSwitcher,
        ApprovalBadge,
        DocumentTitleLockInfo,
        ActionsHeader,
        "permissions-update-modal": () =>
            import(
                /* webpackChunkName: "document-permissions-update-modal" */ "../Folder/Permissions/PermissionsUpdateModal.vue"
            ),
        "create-new-embedded-file-version-modal": () =>
            import(
                /* webpackChunkName: "document-new-embedded-file-version-modal" */ "../Folder/DropDown/NewVersion/CreateNewVersionEmbeddedFileModal.vue"
            ),
        "confirm-deletion-modal": () =>
            import(
                /* webpackChunkName: "document-confirm-item-deletion-modal" */ "../Folder/DropDown/Delete/ModalConfirmDeletion.vue"
            ),
        "update-properties-modal": () =>
            import(
                /* webpackChunkName: "update-properties-modal" */ "../Folder/DropDown/UpdateProperties/UpdatePropertiesModal.vue"
            ),
    },
});
</script>
