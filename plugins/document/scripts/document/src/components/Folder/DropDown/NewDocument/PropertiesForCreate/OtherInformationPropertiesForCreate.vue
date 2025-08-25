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
    <div
        class="document-properties"
        v-if="has_properties_to_create"
        data-test="document-other-information"
    >
        <hr class="tlp-modal-separator" />
        <div class="document-modal-other-information-title-container">
            <div
                v-if="!has_loaded_properties"
                class="document-modal-other-information-title-container-spinner"
                data-test="document-other-information-spinner"
            >
                <i class="fa-solid fa-spin fa-circle-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle">{{ $gettext("Other information") }}</h2>
        </div>
        <template v-if="has_loaded_properties">
            <obsolescence-date-property-for-create
                v-if="is_obsolescence_date_property_used"
                v-bind:value="value"
            />
            <custom-property v-bind:item-property="currentlyUpdatedItem.properties" />
        </template>
    </div>
</template>

<script setup lang="ts">
import ObsolescenceDatePropertyForCreate from "./ObsolescenceDatePropertyForCreate.vue";
import CustomProperty from "../../PropertiesForCreateOrUpdate/CustomProperties/CustomProperty.vue";
import { useNamespacedActions, useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../../store/configuration";
import type { PropertiesState } from "../../../../../store/properties/module";
import { computed, onMounted } from "vue";
import type { PropertiesActions } from "../../../../../store/properties/properties-actions";
import type { Item } from "../../../../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT_ID } from "../../../../../configuration-keys";

const props = defineProps<{ currentlyUpdatedItem: Item; value: string }>();

const project_id = strictInject(PROJECT_ID);

const { loadProjectProperties } = useNamespacedActions<PropertiesActions>("properties", [
    "loadProjectProperties",
]);

const { is_obsolescence_date_property_used } = useNamespacedState<
    Pick<ConfigurationState, "is_obsolescence_date_property_used">
>("configuration", ["is_obsolescence_date_property_used"]);

const { has_loaded_properties } = useNamespacedState<
    Pick<PropertiesState, "has_loaded_properties">
>("properties", ["has_loaded_properties"]);

onMounted((): void => {
    if (!has_loaded_properties.value) {
        loadProjectProperties(project_id);
    }
});

const has_properties_to_create = computed((): boolean => {
    return (
        is_obsolescence_date_property_used.value ||
        (props.currentlyUpdatedItem.properties && props.currentlyUpdatedItem.properties.length > 0)
    );
});
</script>
