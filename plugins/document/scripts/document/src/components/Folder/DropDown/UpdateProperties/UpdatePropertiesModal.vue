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
        aria-labelled-by="document-update-file-properties-modal"
        v-on:submit="updateProperties"
        ref="form"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            aria-labelled-by="document-update-file-properties-modal"
        />
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body">
            <document-global-property-for-update
                v-bind:parent="current_folder"
                v-bind:currently-updated-item="item_to_update"
                v-bind:status_value="item_to_update.status"
            />
            <other-information-properties-for-update
                v-bind:currently-updated-item="item_to_update"
                v-bind:property-to-update="formatted_item_properties"
                v-bind:value="obsolescence_date_value"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Update properties')"
            aria-labelled-by="document-update-file-properties-modal"
            v-bind:icon-submit-button-class="'fa-solid fa-pencil'"
            data-test="document-modal-submit-update-properties"
        />
    </form>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import DocumentGlobalPropertyForUpdate from "./DocumentGlobalPropertyForUpdate.vue";
import OtherInformationPropertiesForUpdate from "./OtherInformationPropertiesForUpdate.vue";
import { getCustomProperties } from "../../../../helpers/properties-helpers/custom-properties-helper";
import {
    transformCustomPropertiesForItemUpdate,
    transformDocumentPropertiesForUpdate,
} from "../../../../helpers/properties-helpers/update-data-transformatter-helper";
import type { UpdateCustomEvent, UpdateMultipleListValueEvent } from "../../../../helpers/emitter";
import emitter from "../../../../helpers/emitter";
import type { Item, Property, RootState } from "../../../../type";
import { computed, onBeforeMount, onBeforeUnmount, onMounted, ref, toRaw } from "vue";
import { useNamespacedState, useState, useStore } from "vuex-composition-helpers";
import type { ErrorState } from "../../../../store/error/module";
import { useGettext } from "vue3-gettext";
import { IS_STATUS_PROPERTY_USED } from "../../../../configuration-keys";
import { strictInject } from "@tuleap/vue-strict-inject";

const { $gettext } = useGettext();
const $store = useStore();

const props = defineProps<{
    item: Item;
}>();

const emit = defineEmits<{
    (e: "update-properties-modal-closed"): void;
}>();

const item_to_update = ref<Item>(structuredClone(toRaw(props.item)));
const is_loading = ref<boolean>(false);
let modal: Modal | null = null;
const formatted_item_properties = ref<Array<Property>>([]);
const form = ref<HTMLFormElement>();

const { current_folder } = useState<Pick<RootState, "current_folder">>(["current_folder"]);
const is_status_property_used = strictInject(IS_STATUS_PROPERTY_USED);
const { has_modal_error, has_global_modal_error } = useNamespacedState<
    Pick<ErrorState, "has_modal_error" | "has_global_modal_error">
>("error", ["has_modal_error", "has_global_modal_error"]);

const modal_title = computed(() => sprintf($gettext('Edit "%s" properties'), props.item.title));
const obsolescence_date_value = computed(() => {
    if (!props.item.properties) {
        return "";
    }

    const obsolescence_date = props.item.properties.find(
        (property) => property.short_name === "obsolescence_date",
    );

    if (!obsolescence_date || !obsolescence_date.value) {
        return "";
    }
    return obsolescence_date.value;
});

onBeforeMount(() => {
    transformDocumentPropertiesForUpdate(item_to_update.value, is_status_property_used);
});

onMounted(() => {
    emitter.on("update-multiple-properties-list-value", updateMultiplePropertiesListValue);
    emitter.on("update-status-property", updateStatusValue);
    emitter.on("update-title-property", updateTitleValue);
    emitter.on("update-description-property", updateDescriptionValue);
    emitter.on("update-owner-property", updateOwnerValue);
    emitter.on("update-custom-property", updateCustomProperty);
    emitter.on("update-obsolescence-date-property", updateObsolescenceDateProperty);

    if (has_global_modal_error.value) {
        return;
    }

    modal = createModal(form.value);

    formatted_item_properties.value = getCustomProperties(item_to_update.value);
    transformCustomPropertiesForItemUpdate(formatted_item_properties.value);

    registerEvents();

    show();
});

onBeforeUnmount(() => {
    emitter.off("update-multiple-properties-list-value", updateMultiplePropertiesListValue);
    emitter.off("update-status-property", updateStatusValue);
    emitter.off("update-title-property", updateTitleValue);
    emitter.off("update-description-property", updateDescriptionValue);
    emitter.off("update-owner-property", updateOwnerValue);
    emitter.off("update-custom-property", updateCustomProperty);
    emitter.off("update-obsolescence-date-property", updateObsolescenceDateProperty);
});

function show(): void {
    modal?.show();
}

function registerEvents(): void {
    modal?.addEventListener("tlp-modal-hidden", reset);
}

function reset(): void {
    $store.commit("error/resetModalError");
    emit("update-properties-modal-closed");
}

async function updateProperties(event: SubmitEvent): Promise<void> {
    event.preventDefault();
    is_loading.value = true;
    $store.commit("error/resetModalError");

    item_to_update.value.properties = formatted_item_properties.value;
    try {
        await $store.dispatch("properties/updateProperties", {
            item: props.item,
            item_to_update: item_to_update.value,
            current_folder: current_folder.value,
        });
        is_loading.value = false;
        if (!has_modal_error.value) {
            modal?.hide();
        }
    } catch (exception) {
        is_loading.value = false;
        throw exception;
    }
}

function updateMultiplePropertiesListValue(event: UpdateMultipleListValueEvent): void {
    if (!formatted_item_properties.value) {
        return;
    }
    const item_properties = formatted_item_properties.value.find(
        (property) => property.short_name === event.detail.id,
    );
    if (item_properties) {
        item_properties.list_value = event.detail.value;
    }
}

function updateStatusValue(status: string): void {
    item_to_update.value.status = status;
}

function updateTitleValue(title: string): void {
    item_to_update.value.title = title;
}

function updateDescriptionValue(description: string): void {
    item_to_update.value.description = description;
}

function updateOwnerValue(owner: number): void {
    item_to_update.value.owner.id = owner;
}

function updateCustomProperty(event: UpdateCustomEvent): void {
    if (!formatted_item_properties.value) {
        return;
    }
    const item_properties = formatted_item_properties.value.find(
        (property) => property.short_name === event.property_short_name,
    );
    if (item_properties) {
        item_properties.value = event.value;
    }
}

function updateObsolescenceDateProperty(event: string): void {
    item_to_update.value.obsolescence_date = parseInt(event, 10);
}
</script>
