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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div v-if="has_recursion_property" data-test="document-folder-default-properties-container">
        <hr class="tlp-modal-separator" />
        <div class="document-modal-other-information-title-container">
            <div
                v-if="!has_loaded_properties"
                class="document-modal-other-information-title-container-spinner"
                data-test="document-folder-default-properties-spinner"
            >
                <i class="fa-solid fa-spin fa-circle-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle">
                {{ $gettext("Default properties") }}
            </h2>
        </div>
        <template v-if="has_loaded_properties">
            <p>
                {{
                    $gettext(
                        "All the properties values that you define here will be proposed as default values for the items that will be created within this folder.",
                    )
                }}"
            </p>
            <div class="document-default-properties">
                <div class="document-properties-container" v-if="is_status_property_used">
                    <div class="document-recursion-checkbox-container">
                        <label class="tlp-label"><i class="fa-solid fa-rotate-right"></i></label>
                        <input
                            id="status"
                            type="checkbox"
                            class="document-recursion-checkbox"
                            value="status"
                            ref="status_input"
                            v-on:change="updateStatusRecursion"
                            data-test="document-status-property-recursion-input"
                        />
                    </div>
                    <status-property-with-custom-binding-for-folder-update
                        v-bind:status_value="status_value"
                    />
                </div>
                <div
                    v-for="custom in itemProperty"
                    v-bind:key="custom.short_name"
                    class="document-properties-container"
                >
                    <div class="document-recursion-checkbox-container">
                        <label class="tlp-label"><i class="fa-solid fa-rotate-right"></i></label>
                        <input
                            v-bind:id="custom.short_name"
                            type="checkbox"
                            class="document-recursion-checkbox"
                            v-on:change="updatePropertiesWithRecursion"
                            v-model="properties_to_update"
                            v-bind:value="custom.short_name"
                            data-test="document-custom-property-checkbox"
                        />
                    </div>
                    <custom-property-component-type-renderer
                        v-bind:item-property="custom"
                        v-on:input="custom.value = $event.target.value"
                    />
                </div>
                <recursion-options
                    v-bind:value="recursion_option"
                    v-on:update-recursion-option="updateRecursionOption"
                    data-test="document-custom-property-recursion-option"
                />
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import StatusPropertyWithCustomBindingForFolderUpdate from "./StatusPropertyWithCustomBindingForFolderUpdate.vue";
import CustomPropertyComponentTypeRenderer from "../PropertiesForCreateOrUpdate/CustomProperties/CustomPropertyComponentTypeRenderer.vue";
import RecursionOptions from "../PropertiesForCreateOrUpdate/RecursionOptions.vue";
import emitter from "../../../../helpers/emitter";
import type { Item, Property } from "../../../../type";
import { computed, onMounted, ref } from "vue";
import { useNamespacedActions, useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";
import type { PropertiesState } from "../../../../store/properties/module";
import type { PropertiesActions } from "../../../../store/properties/properties-actions";

const props = defineProps<{
    itemProperty: Array<Property>;
    currentlyUpdatedItem: Item;
    status_value: string;
    recursion_option: string;
}>();

let list_of_properties_to_update: Array<string> = [];
let properties_to_update = ref(list_of_properties_to_update);
let status_input = ref<InstanceType<typeof HTMLInputElement>>();

const { is_status_property_used } = useNamespacedState<
    Pick<ConfigurationState, "is_status_property_used">
>("configuration", ["is_status_property_used"]);

const { has_loaded_properties } = useNamespacedState<
    Pick<PropertiesState, "has_loaded_properties">
>("properties", ["has_loaded_properties"]);

const { loadProjectProperties } = useNamespacedActions<PropertiesActions>("properties", [
    "loadProjectProperties",
]);

const has_recursion_property = computed((): boolean => {
    return is_status_property_used.value || props.itemProperty.length > 0;
});

onMounted((): void => {
    if (!has_loaded_properties.value) {
        loadProjectProperties();
    }
});

function updatePropertiesWithRecursion(): void {
    emitter.emit("properties-recursion-list", {
        detail: { property_list: properties_to_update.value },
    });
}

function updateStatusRecursion(): void {
    if (!is_status_property_used.value) {
        return;
    }

    let must_use_recursion = false;
    if (status_input.value && status_input.value.checked) {
        must_use_recursion = true;
    }

    emitter.emit("update-status-recursion", must_use_recursion);
}

function updateRecursionOption(event: string): void {
    if (event !== "none") {
        props.itemProperty.forEach((property) => {
            return properties_to_update.value.push(property.short_name);
        });
        if (is_status_property_used.value && status_input.value) {
            properties_to_update.value.push("status");
            status_input.value.checked = true;
        }
    } else if (status_input.value) {
        status_input.value.checked = false;
        properties_to_update.value = [];
    } else {
        properties_to_update.value = [];
    }
    updatePropertiesWithRecursion();
    emitter.emit("properties-recursion-option", {
        recursion_option: event,
    });
}
</script>
