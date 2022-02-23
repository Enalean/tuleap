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
    <div
        class="tlp-form-element"
        v-if="
            currentlyUpdatedItemProperty.type === 'list' &&
            currentlyUpdatedItemProperty.is_multiple_value_allowed
        "
        data-test="document-custom-property-list-multiple"
    >
        <label class="tlp-label" v-bind:for="`document-${currentlyUpdatedItemProperty.short_name}`">
            {{ currentlyUpdatedItemProperty.name }}
            <i
                class="fa fa-asterisk"
                v-if="currentlyUpdatedItemProperty.is_required"
                data-test="document-custom-property-is-required"
            ></i>
        </label>
        <select
            class="tlp-form-element tlp-select"
            v-bind:id="`document-${currentlyUpdatedItemProperty.short_name}`"
            multiple
            v-model="multiple_list_values"
            data-test="document-custom-list-multiple-select"
            v-on:change="updatePropertiesListValue"
        >
            <option
                v-for="possible_value in project_properties_list_possible_values.allowed_list_values"
                v-bind:key="possible_value.id"
                v-bind:value="possible_value.id"
                v-bind:data-test="`document-custom-list-multiple-value-${possible_value.id}`"
                v-bind:required="currentlyUpdatedItemProperty.is_required"
            >
                {{ possible_value.value }}
            </option>
        </select>
    </div>
</template>

<script>
import { mapState } from "vuex";
import emitter from "../../../../../helpers/emitter";

export default {
    name: "CustomPropertyListMultipleValue",
    props: {
        currentlyUpdatedItemProperty: Object,
    },
    data() {
        return {
            project_properties_list_possible_values: [],
            multiple_list_values: this.currentlyUpdatedItemProperty.list_value,
        };
    },
    computed: {
        ...mapState("properties", ["project_properties"]),
    },
    mounted() {
        if (
            this.currentlyUpdatedItemProperty.is_multiple_value_allowed &&
            this.currentlyUpdatedItemProperty.type === "list"
        ) {
            this.project_properties_list_possible_values = this.project_properties.find(
                ({ short_name }) => short_name === this.currentlyUpdatedItemProperty.short_name
            );
        }
    },
    methods: {
        updatePropertiesListValue() {
            emitter.emit("update-multiple-properties-list-value", {
                detail: {
                    value: this.multiple_list_values,
                    id: this.currentlyUpdatedItemProperty.short_name,
                },
            });
        },
    },
};
</script>
