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
            <i class="fa fa-asterisk" v-if="currentlyUpdatedItemProperty.is_required"></i>
        </label>
        <select
            class="tlp-form-element tlp-select"
            v-bind:id="`document-${currentlyUpdatedItemProperty.short_name}`"
            v-on:input="$emit('input', $event.target.value)"
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

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import type { ListValue, Property } from "../../../../../store/properties/module";
import { namespace } from "vuex-class";

const properties = namespace("properties");

@Component
export default class CustomPropertyListSingleValue extends Vue {
    @Prop({ required: true })
    readonly currentlyUpdatedItemProperty!: Property;

    @properties.State
    readonly project_properties!: Array<Property>;

    private value = String(this.currentlyUpdatedItemProperty.value);
    private project_properties_list_possible_values: Array<ListValue> | null = [];

    mounted(): void {
        const values = this.project_properties.find(
            ({ short_name }) => short_name === this.currentlyUpdatedItemProperty.short_name
        )?.allowed_list_values;
        if (!values) {
            return;
        }
        this.project_properties_list_possible_values = values;
    }
}
</script>
