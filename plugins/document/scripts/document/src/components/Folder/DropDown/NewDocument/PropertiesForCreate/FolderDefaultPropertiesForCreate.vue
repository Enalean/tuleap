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
                v-if="project_properties === null"
                class="document-modal-other-information-title-container-spinner"
                data-test="document-folder-default-properties-spinner"
            >
                <i class="fa-solid fa-spin fa-circle-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle">{{ $gettext("Default properties") }}</h2>
        </div>
        <template v-if="project_properties !== null">
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
import { useStore } from "vuex-composition-helpers";
import { computed, onMounted } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { IS_STATUS_PROPERTY_USED, PROJECT } from "../../../../../configuration-keys";
import type { DocumentProperties } from "../../../../../helpers/properties/document-properties";
import { PROJECT_PROPERTIES } from "../../../../../injection-keys";

const $store = useStore();

const props = defineProps<{
    status_value: string;
    properties: Array<Property>;
    document_properties: DocumentProperties;
}>();

const project = strictInject(PROJECT);
const is_status_property_used = strictInject(IS_STATUS_PROPERTY_USED);
const project_properties = strictInject(PROJECT_PROPERTIES);

onMounted((): void => {
    if (project_properties.value === null) {
        props.document_properties.loadProjectProperties($store, project.id).map((properties) => {
            project_properties.value = properties;
            return null;
        });
    }
});

const has_recursion_property = computed((): boolean => {
    return (
        is_status_property_used === true ||
        (props.properties !== null && props.properties.length > 0)
    );
});
</script>
