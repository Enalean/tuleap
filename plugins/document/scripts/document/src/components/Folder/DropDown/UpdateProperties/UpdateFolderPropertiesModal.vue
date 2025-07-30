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
        aria-labelledby="document-update-folder-properties-modal"
        v-on:submit="updateProperties"
        ref="form"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            aria-labelled-by="document-update-folder-properties-modal"
        />
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body">
            <folder-global-property-for-update
                v-bind:currently-updated-item="item_to_update"
                v-bind:parent="current_folder"
                v-bind:item-property="formatted_item_properties"
                v-bind:status_value="item_to_update.status.value"
                v-bind:recursion_option="recursion_option"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Update properties')"
            aria-labelled-by="document-update-folder-properties-modal"
            v-bind:icon-submit-button-class="'fa-solid fa-pencil'"
            data-test="document-modal-submit-button-update-properties"
        />
    </form>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import FolderGlobalPropertyForUpdate from "./FolderGlobalPropertyForUpdate.vue";
import {
    formatCustomPropertiesForFolderUpdate,
    transformCustomPropertiesForItemUpdate,
    transformFolderPropertiesForRecursionAtUpdate,
} from "../../../../helpers/properties-helpers/update-data-transformatter-helper";
import { getCustomProperties } from "../../../../helpers/properties-helpers/custom-properties-helper";
import type {
    UpdateCustomEvent,
    UpdateMultipleListValueEvent,
    UpdatePropertyListEvent,
    UpdateRecursionOptionEvent,
} from "../../../../helpers/emitter";
import emitter from "../../../../helpers/emitter";
import type { Folder, Property, RootState } from "../../../../type";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useNamespacedState, useState, useStore } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";
import type { ErrorState } from "../../../../store/error/module";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const $store = useStore();

const props = defineProps<{
    item: Folder;
}>();

const { current_folder } = useState<Pick<RootState, "current_folder">>(["current_folder"]);
const { is_status_property_used } = useNamespacedState<
    Pick<ConfigurationState, "is_status_property_used">
>("configuration", ["is_status_property_used"]);
const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);

const item_to_update = ref<Folder>(
    transformFolderPropertiesForRecursionAtUpdate(props.item, is_status_property_used.value),
);
const is_loading = ref<boolean>(false);
const recursion_option = ref<string>("none");
const properties_to_update = ref<string[]>([]);
const formatted_item_properties = ref<Property[]>([]);
const form = ref<HTMLFormElement>();
let modal: Modal | null = null;

const modal_title = computed(() => sprintf($gettext('Edit "%s" properties'), props.item.title));

onMounted(() => {
    modal = createModal(form.value);

    formatted_item_properties.value = getCustomProperties(props.item);

    transformCustomPropertiesForItemUpdate(formatted_item_properties.value);

    show();

    emitter.on("properties-recursion-list", setPropertiesListUpdate);
    emitter.on("properties-recursion-option", setRecursionOption);
    emitter.on("update-multiple-properties-list-value", updateMultiplePropertiesListValue);
    if (is_status_property_used.value) {
        emitter.on("update-status-property", updateStatusValue);
        emitter.on("update-status-recursion", updateStatusRecursion);
    }
    emitter.on("update-title-property", updateTitleValue);
    emitter.on("update-description-property", updateDescriptionValue);
    emitter.on("update-custom-property", updateCustomProperty);
});

onBeforeUnmount(() => {
    emitter.off("properties-recursion-list", setPropertiesListUpdate);
    emitter.off("properties-recursion-option", setRecursionOption);
    emitter.off("update-multiple-properties-list-value", updateMultiplePropertiesListValue);
    if (is_status_property_used.value) {
        emitter.off("update-status-property", updateStatusValue);
        emitter.off("update-status-recursion", updateStatusRecursion);
    }
    emitter.off("update-title-property", updateTitleValue);
    emitter.off("update-description-property", updateDescriptionValue);
    emitter.off("update-custom-property", updateCustomProperty);
});

function show(): void {
    modal?.show();
}

async function updateProperties(event: SubmitEvent): Promise<void> {
    event.preventDefault();
    is_loading.value = true;
    $store.commit("error/resetModalError");
    item_to_update.value.properties = formatted_item_properties.value;
    formatCustomPropertiesForFolderUpdate(
        item_to_update.value,
        properties_to_update.value,
        recursion_option.value,
    );
    await $store.dispatch("properties/updateFolderProperties", {
        item: props.item,
        item_to_update: item_to_update.value,
        current_folder: current_folder.value,
        properties_to_update: properties_to_update.value,
        recursion_option: recursion_option.value,
    });
    is_loading.value = false;
    if (!has_modal_error.value) {
        modal?.hide();
    }
}

function setPropertiesListUpdate(event: UpdatePropertyListEvent): void {
    properties_to_update.value = event.detail.property_list;
}

function setRecursionOption(event: UpdateRecursionOptionEvent): void {
    recursion_option.value = event.recursion_option;
    if (is_status_property_used.value) {
        item_to_update.value.status.recursion = event.recursion_option;
    }
}

function updateMultiplePropertiesListValue(event: UpdateMultipleListValueEvent): void {
    if (!formatted_item_properties.value) {
        return;
    }
    const item_properties = formatted_item_properties.value.find(
        (property) => property.short_name === event.detail.id,
    );
    if (item_properties !== undefined) {
        item_properties.list_value = event.detail.value;
    }
}

function updateStatusValue(status: string): void {
    item_to_update.value.status.value = status;
}

function updateStatusRecursion(must_use_recursion: boolean): void {
    let status_recursion = "none";
    if (must_use_recursion) {
        status_recursion = recursion_option.value;
    }

    item_to_update.value.status.recursion = status_recursion;
}

function updateTitleValue(title: string): void {
    item_to_update.value.title = title;
}

function updateDescriptionValue(description: string): void {
    item_to_update.value.description = description;
}

function updateCustomProperty(event: UpdateCustomEvent): void {
    if (!formatted_item_properties.value) {
        return;
    }
    const item_properties = formatted_item_properties.value.find(
        (property) => property.short_name === event.property_short_name,
    );
    if (item_properties !== undefined) {
        item_properties.value = event.value;
    }
}
</script>
