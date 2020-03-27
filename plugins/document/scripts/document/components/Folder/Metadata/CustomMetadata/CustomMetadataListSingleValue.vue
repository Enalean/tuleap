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
            currentlyUpdatedItemMetadata.type === 'list' &&
            !currentlyUpdatedItemMetadata.is_multiple_value_allowed
        "
        data-test="document-custom-metadata-list"
    >
        <label class="tlp-label" v-bind:for="`document-${currentlyUpdatedItemMetadata.short_name}`">
            {{ currentlyUpdatedItemMetadata.name }}
            <i class="fa fa-asterisk" v-if="currentlyUpdatedItemMetadata.is_required"></i>
        </label>
        <select
            class="tlp-form-element tlp-select"
            v-bind:id="`document-${currentlyUpdatedItemMetadata.short_name}`"
            v-on:input="$emit('input', $event.target.value)"
            v-model="currentlyUpdatedItemMetadata.value"
            v-bind:required="currentlyUpdatedItemMetadata.is_required"
            data-test="document-custom-list-select"
        >
            <option
                v-for="possible_value in project_metadata_list_possible_values.allowed_list_values"
                v-bind:key="possible_value.id"
                v-bind:value="possible_value.id"
                v-bind:selected="possible_value.id === currentlyUpdatedItemMetadata.value"
                v-bind:data-test="`document-custom-list-value-${possible_value.id}`"
            >
                {{ possible_value.value }}
            </option>
        </select>
    </div>
</template>

<script>
import { mapState } from "vuex";
export default {
    name: "CustomMetadataListSingleValue",
    props: {
        currentlyUpdatedItemMetadata: Object,
    },
    data() {
        return {
            project_metadata_list_possible_values: [],
        };
    },
    computed: {
        ...mapState("metadata", ["project_metadata_list"]),
    },
    mounted() {
        this.project_metadata_list_possible_values = this.project_metadata_list.find(
            ({ short_name }) => short_name === this.currentlyUpdatedItemMetadata.short_name
        );
    },
};
</script>
