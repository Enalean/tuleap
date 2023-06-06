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
                v-bind:item="embedded_file"
                v-bind:is-displaying-in-header="true"
            />

            <h1 class="document-header-title">
                {{ embedded_file.title }}
            </h1>

            <actions-header v-bind:item="embedded_file" />

            <approval-badge v-bind:item="embedded_file" v-bind:is-in-folder-content-row="false" />

            <embedded-file-edition-switcher v-bind:is-in-large-view="is_embedded_in_large_view" />
        </div>

        <div
            class="embedded-document-container-warning"
            v-if="should_display_old_version_warning"
            data-test="warning"
        >
            <div class="tlp-alert-warning">
                {{ specific_version_warning }}
                <router-link
                    v-bind:to="{
                        name: 'item',
                        params: { folder_id: embedded_file.parent_id, item_id: embedded_file.id },
                    }"
                >
                    {{ go_to_last_version }}
                </router-link>
            </div>
        </div>

        <section
            class="tlp-pane embedded-document"
            v-bind:class="{ narrow: !is_embedded_in_large_view }"
            data-test="display-embedded-content"
        >
            <div class="tlp-pane-container">
                <section class="tlp-pane-section" v-dompurify-html="content_to_display"></section>
            </div>
        </section>

        <create-new-embedded-file-version-modal
            v-if="is_modal_shown"
            v-bind:item="embedded_file"
            v-on:hidden="hideModal()"
        />
        <confirm-deletion-modal
            v-if="show_confirm_deletion_modal"
            v-bind:item="embedded_file"
            v-bind:should-redirect-to-parent-after-deletion="true"
            v-on:delete-modal-closed="hideDeleteItemModal"
        />

        <update-properties-modal
            v-if="show_update_properties_modal"
            v-bind:item="embedded_file"
            v-on:update-properties-modal-closed="hideUpdatePropertiesModal"
        />
        <permissions-update-modal
            v-if="show_update_permissions_modal"
            v-bind:item="embedded_file"
        />
    </div>
</template>

<script setup lang="ts">
import emitter from "../../helpers/emitter";
// eslint-disable-next-line import/no-duplicates
import { computed, onBeforeMount, onUnmounted, ref } from "vue";
import type { PreferenciesState } from "../../store/preferencies/preferencies-default-state";
import { useNamespacedState } from "vuex-composition-helpers";
import type { Embedded } from "../../type";
import { useGettext } from "vue3-gettext";

const is_modal_shown = ref(false);
const show_confirm_deletion_modal = ref(false);
const show_update_properties_modal = ref(false);
const show_update_permissions_modal = ref(false);

const props = withDefaults(
    defineProps<{
        embedded_file: Embedded;
        content_to_display: string;
        specific_version_number?: number | null;
    }>(),
    { specific_version_number: null }
);

const { is_embedded_in_large_view } = useNamespacedState<PreferenciesState>("preferencies", [
    "is_embedded_in_large_view",
]);

const { interpolate, $gettext } = useGettext();

const should_display_old_version_warning = computed((): boolean => {
    if (!props.specific_version_number) {
        return false;
    }

    return (
        props.specific_version_number !==
        props.embedded_file.embedded_file_properties?.version_number
    );
});

const specific_version_warning = computed((): string => {
    if (!props.specific_version_number) {
        return "";
    }

    return interpolate(
        $gettext("You are viewing an old version (%{ version_number }) of this document."),
        { version_number: props.specific_version_number }
    );
});
const go_to_last_version = computed((): string => {
    if (!props.specific_version_number) {
        return "";
    }

    return interpolate($gettext("Go to the last version (%{ version_number })."), {
        version_number: props.embedded_file.embedded_file_properties?.version_number,
    });
});

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
// eslint-disable-next-line import/no-duplicates
import { defineAsyncComponent, defineComponent } from "vue";
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
        "permissions-update-modal": defineAsyncComponent(
            () =>
                import(
                    /* webpackChunkName: "document-permissions-update-modal" */ "../Folder/Permissions/PermissionsUpdateModal.vue"
                )
        ),
        "create-new-embedded-file-version-modal": defineAsyncComponent(
            () =>
                import(
                    /* webpackChunkName: "document-new-embedded-file-version-modal" */ "../Folder/DropDown/NewVersion/CreateNewVersionEmbeddedFileModal.vue"
                )
        ),
        "confirm-deletion-modal": defineAsyncComponent(
            () =>
                import(
                    /* webpackChunkName: "document-confirm-item-deletion-modal" */ "../Folder/DropDown/Delete/ModalConfirmDeletion.vue"
                )
        ),
        "update-properties-modal": defineAsyncComponent(
            () =>
                import(
                    /* webpackChunkName: "update-properties-modal" */ "../Folder/DropDown/UpdateProperties/UpdatePropertiesModal.vue"
                )
        ),
    },
});
</script>
