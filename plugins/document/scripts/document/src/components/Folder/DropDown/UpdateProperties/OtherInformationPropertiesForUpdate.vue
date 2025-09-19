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
        v-if="should_display_other_information"
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
            <obsolescence-date-property-for-update
                v-if="is_obsolescence_date_property_used"
                v-bind:value="value"
            />
            <custom-property v-bind:item-property="propertyToUpdate" />
        </template>
    </div>
</template>

<script setup lang="ts">
import ObsolescenceDatePropertyForUpdate from "./ObsolescenceDatePropertyForUpdate.vue";
import CustomProperty from "../PropertiesForCreateOrUpdate/CustomProperties/CustomProperty.vue";
import type { Item, Property } from "../../../../type";
import { useNamespacedActions, useNamespacedState } from "vuex-composition-helpers";
import type { PropertiesState } from "../../../../store/properties/module";
import { computed, onMounted } from "vue";
import type { PropertiesActions } from "../../../../store/properties/properties-actions";
import { strictInject } from "@tuleap/vue-strict-inject";
import { IS_OBSOLESCENCE_DATE_PROPERTY_USED, PROJECT } from "../../../../configuration-keys";

const props = defineProps<{
    currentlyUpdatedItem: Item;
    propertyToUpdate: Array<Property>;
    value: string;
}>();

const project = strictInject(PROJECT);
const is_obsolescence_date_property_used = strictInject(IS_OBSOLESCENCE_DATE_PROPERTY_USED);

const { has_loaded_properties } = useNamespacedState<
    Pick<PropertiesState, "has_loaded_properties">
>("properties", ["has_loaded_properties"]);

const should_display_other_information = computed((): boolean => {
    return is_obsolescence_date_property_used || props.propertyToUpdate.length > 0;
});

const { loadProjectProperties } = useNamespacedActions<PropertiesActions>("properties", [
    "loadProjectProperties",
]);

onMounted((): void => {
    if (!has_loaded_properties.value) {
        loadProjectProperties(project.id);
    }
});
</script>
