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
        class="tlp-form-element"
        v-if="
            currentlyUpdatedItemProperty.type === 'list' &&
            !currentlyUpdatedItemProperty.is_multiple_value_allowed
        "
        data-test="document-custom-property-list"
    >
        <label class="tlp-label" v-bind:for="`document-${currentlyUpdatedItemProperty.short_name}`">
            {{ currentlyUpdatedItemProperty.name }}
            <i class="fa-solid fa-asterisk" v-if="currentlyUpdatedItemProperty.is_required"></i>
        </label>
        <select
            class="tlp-form-element tlp-select"
            v-bind:id="`document-${currentlyUpdatedItemProperty.short_name}`"
            v-model="value"
            v-bind:required="currentlyUpdatedItemProperty.is_required"
            data-test="document-custom-list-select"
        >
            <option
                v-for="possible_value in project_properties_list_possible_values"
                v-bind:key="possible_value.id"
                v-bind:value="possible_value.id"
                v-bind:selected="possible_value.id === currentlyUpdatedItemProperty.value"
                v-bind:data-test="`document-custom-list-value-${possible_value.id}`"
            >
                {{ possible_value.value }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
import type { ListValue, Property } from "../../../../../type";
import emitter from "../../../../../helpers/emitter";
import { useNamespacedState } from "vuex-composition-helpers";
import type { PropertiesState } from "../../../../../store/properties/module";
import { computed, onMounted, ref } from "vue";

const props = defineProps<{ currentlyUpdatedItemProperty: Property }>();

const { project_properties } = useNamespacedState<Pick<PropertiesState, "project_properties">>(
    "properties",
    ["project_properties"],
);

const value = computed({
    get() {
        return String(props.currentlyUpdatedItemProperty.value);
    },
    set(value) {
        emitter.emit("update-custom-property", {
            property_short_name: props.currentlyUpdatedItemProperty.short_name,
            value: String(value),
        });
    },
});

const project_properties_list_possible_values = ref<Array<ListValue> | null>([]);

onMounted((): void => {
    const values = project_properties.value.find(
        ({ short_name }) => short_name === props.currentlyUpdatedItemProperty.short_name,
    )?.allowed_list_values;
    if (!values) {
        return;
    }
    project_properties_list_possible_values.value = values;
});
</script>
