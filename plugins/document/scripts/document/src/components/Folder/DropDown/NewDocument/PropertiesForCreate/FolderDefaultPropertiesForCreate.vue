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
            <h2 class="tlp-modal-subtitle">{{ $gettext("Default properties") }}</h2>
        </div>
        <template v-if="has_loaded_properties">
            <p data-test="document-folder-default-properties">
                {{
                    $gettext(
                        "All the properties values that you define here will be proposed as default values for the items that will be created within this folder.",
                    )
                }}
            </p>
            <status-property-with-custom-binding-for-folder-create
                v-bind:status_value="status_value"
            />
            <custom-property v-bind:item-property="properties" />
        </template>
    </div>
</template>

<script setup lang="ts">
import StatusPropertyWithCustomBindingForFolderCreate from "./StatusPropertyWithCustomBindingForFolderCreate.vue";
import CustomProperty from "../../PropertiesForCreateOrUpdate/CustomProperties/CustomProperty.vue";
import type { Property } from "../../../../../type";
import { useNamespacedActions, useNamespacedState } from "vuex-composition-helpers";
import type { PropertiesState } from "../../../../../store/properties/module";
import { computed, onMounted } from "vue";
import type { PropertiesActions } from "../../../../../store/properties/properties-actions";
import { strictInject } from "@tuleap/vue-strict-inject";
import { IS_STATUS_PROPERTY_USED, PROJECT } from "../../../../../configuration-keys";

const props = defineProps<{ status_value: string; properties: Array<Property> }>();

const project = strictInject(PROJECT);
const is_status_property_used = strictInject(IS_STATUS_PROPERTY_USED);

const { loadProjectProperties } = useNamespacedActions<PropertiesActions>("properties", [
    "loadProjectProperties",
]);

const { has_loaded_properties } = useNamespacedState<
    Pick<PropertiesState, "has_loaded_properties">
>("properties", ["has_loaded_properties"]);

onMounted((): void => {
    if (!has_loaded_properties.value) {
        loadProjectProperties(project.id);
    }
});

const has_recursion_property = computed((): boolean => {
    return (
        is_status_property_used === true ||
        (props.properties !== null && props.properties.length > 0)
    );
});
</script>
