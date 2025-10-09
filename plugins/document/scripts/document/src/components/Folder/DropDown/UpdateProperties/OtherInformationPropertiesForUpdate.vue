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
                v-if="project_properties === null"
                class="document-modal-other-information-title-container-spinner"
                data-test="document-other-information-spinner"
            >
                <i class="fa-solid fa-spin fa-circle-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle">{{ $gettext("Other information") }}</h2>
        </div>
        <template v-if="project_properties !== null">
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
import { useStore } from "vuex-composition-helpers";
import { computed, onMounted } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { IS_OBSOLESCENCE_DATE_PROPERTY_USED, PROJECT } from "../../../../configuration-keys";
import { PROJECT_PROPERTIES } from "../../../../injection-keys";
import type { DocumentProperties } from "../../../../helpers/properties/document-properties";

const $store = useStore();

const props = defineProps<{
    currentlyUpdatedItem: Item;
    propertyToUpdate: Array<Property>;
    value: string;
    document_properties: DocumentProperties;
}>();

const project = strictInject(PROJECT);
const is_obsolescence_date_property_used = strictInject(IS_OBSOLESCENCE_DATE_PROPERTY_USED);
const project_properties = strictInject(PROJECT_PROPERTIES);

const should_display_other_information = computed((): boolean => {
    return is_obsolescence_date_property_used || props.propertyToUpdate.length > 0;
});

onMounted((): void => {
    if (project_properties.value === null) {
        props.document_properties.loadProjectProperties($store, project.id).map((properties) => {
            project_properties.value = properties;
            return null;
        });
    }
});
</script>
