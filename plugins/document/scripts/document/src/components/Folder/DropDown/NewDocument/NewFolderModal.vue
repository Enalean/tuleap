<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
  -->

<template>
    <form
        class="tlp-modal"
        role="dialog"
        data-test="document-new-folder-modal"
        aria-labelledby="document-new-folder-modal"
        v-on:submit="addFolder"
        ref="form"
    >
        <modal-header
            v-bind:modal-title="$gettext('New folder')"
            aria-labelled-by="document-new-item-modal"
        />
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body" v-if="is_displayed">
            <folder-global-properties-for-create
                v-bind:currently-updated-item="item"
                v-bind:parent="parent"
                v-bind:properties="item.properties"
                v-bind:status_value="item.status ? item.status : ''"
            />
            <creation-modal-permissions-section
                v-if="item.permissions_for_groups"
                v-model="item.permissions_for_groups"
                v-bind:value="item.permissions_for_groups"
                v-bind:project_ugroups="project_ugroups"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Create folder')"
            aria-labelled-by="document-new-item-modal"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-folder"
        />
    </form>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { TYPE_FOLDER } from "../../../../constants";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import FolderGlobalPropertiesForCreate from "./PropertiesForCreate/FolderGlobalPropertiesForCreate.vue";
import CreationModalPermissionsSection from "./CreationModalPermissionsSection.vue";
import { getCustomProperties } from "../../../../helpers/properties-helpers/custom-properties-helper";
import { handleErrors } from "../../../../store/actions-helpers/handle-errors";
import {
    transformCustomPropertiesForItemCreation,
    transformStatusPropertyForItemCreation,
} from "../../../../helpers/properties-helpers/creation-data-transformatter-helper";
import type { UpdateCustomEvent, UpdateMultipleListValueEvent } from "../../../../helpers/emitter";
import emitter from "../../../../helpers/emitter";
import { buildFakeItem } from "../../../../helpers/item-builder";
import { onBeforeUnmount, onMounted, ref } from "vue";
import { useNamespacedState, useState, useStore } from "vuex-composition-helpers";
import type { Item, RootState } from "../../../../type";
import type { ErrorState } from "../../../../store/error/module";
import type { PermissionsState } from "../../../../store/permissions/permissions-default-state";
import { strictInject } from "@tuleap/vue-strict-inject";
import { IS_STATUS_PROPERTY_USED, PROJECT } from "../../../../configuration-keys";

const $store = useStore();

const item = ref({});
const is_loading = ref(false);
const is_displayed = ref(false);
const parent = ref({});
const form = ref<HTMLFormElement>();
let modal: Modal | null = null;

const { current_folder } = useState<Pick<RootState, "current_folder">>(["current_folder"]);
const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);
const { project_ugroups } = useNamespacedState<Pick<PermissionsState, "project_ugroups">>(
    "permissions",
    ["project_ugroups"],
);
const project = strictInject(PROJECT);
const is_status_property_used = strictInject(IS_STATUS_PROPERTY_USED);

onMounted(() => {
    modal = createModal(form.value);
    emitter.on("show-new-folder-modal", show);
    emitter.on("update-multiple-properties-list-value", updateMultiplePropertiesListValue);
    modal.addEventListener("tlp-modal-hidden", reset);
    emitter.on("update-status-property", updateStatusValue);
    emitter.on("update-title-property", updateTitleValue);
    emitter.on("update-description-property", updateDescriptionValue);
    emitter.on("update-custom-property", updateCustomProperty);
});

onBeforeUnmount(() => {
    emitter.off("show-new-folder-modal", show);
    emitter.off("update-multiple-properties-list-value", updateMultiplePropertiesListValue);
    modal?.removeEventListener("tlp-modal-hidden", reset);
    emitter.off("update-status-property", updateStatusValue);
    emitter.off("update-title-property", updateTitleValue);
    emitter.off("update-description-property", updateDescriptionValue);
    emitter.off("update-custom-property", updateCustomProperty);
});

function getDefaultItem() {
    return {
        title: "",
        description: "",
        type: TYPE_FOLDER,
        permissions_for_groups: {
            can_read: [],
            can_write: [],
            can_manage: [],
        },
        properties: [],
    };
}

async function show(event: { detail: { parent: Item } }): Promise<void> {
    item.value = getDefaultItem();
    parent.value = event.detail.parent;
    addParentPropertiesToDefaultItem();
    item.value.permissions_for_groups = JSON.parse(
        JSON.stringify(parent.value.permissions_for_groups),
    );
    is_displayed.value = true;
    modal?.show();
    try {
        await $store.dispatch("permissions/loadProjectUserGroupsIfNeeded", project.id);
    } catch (err) {
        await handleErrors($store, err);
        modal?.hide();
    }
}

function reset(): void {
    $store.commit("error/resetModalError");
    is_displayed.value = false;
    is_loading.value = false;
}

async function addFolder(event: SubmitEvent): Promise<void> {
    event.preventDefault();
    is_loading.value = true;
    $store.commit("error/resetModalError");

    await $store.dispatch("createNewItem", [
        item.value,
        parent.value,
        current_folder.value,
        buildFakeItem(),
    ]);
    is_loading.value = false;
    if (!has_modal_error.value) {
        modal?.hide();
    }
}

function addParentPropertiesToDefaultItem(): void {
    const parent_properties = getCustomProperties(parent.value);

    const formatted_properties = transformCustomPropertiesForItemCreation(parent_properties);
    if (formatted_properties.length > 0) {
        item.value.properties = formatted_properties;
    }

    transformStatusPropertyForItemCreation(item.value, parent.value, is_status_property_used);
}

function updateMultiplePropertiesListValue(event: UpdateMultipleListValueEvent): void {
    if (!item.value.properties) {
        return;
    }
    const item_properties = item.value.properties.find(
        (property) => property.short_name === event.detail.id,
    );
    item_properties.list_value = event.detail.value;
}

function updateStatusValue(status: string): void {
    item.value.status = status;
}

function updateTitleValue(title: string): void {
    item.value.title = title;
}

function updateDescriptionValue(description: string): void {
    item.value.description = description;
}

function updateCustomProperty(event: UpdateCustomEvent): void {
    if (!item.value.properties) {
        return;
    }
    const item_properties = item.value.properties.find(
        (property) => property.short_name === event.property_short_name,
    );

    if (!item_properties) {
        return;
    }
    item_properties.value = event.value;
}
</script>
