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
            v-model="value"
            v-bind:required="currentlyUpdatedItemMetadata.is_required"
            data-test="document-custom-list-select"
        >
            <option
                v-for="possible_value in project_metadata_list_possible_values"
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

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import type { ListValue, Metadata } from "../../../../../store/metadata/module";
import { namespace } from "vuex-class";

const metadata = namespace("metadata");

@Component
export default class CustomMetadataListSingleValue extends Vue {
    @Prop({ required: true })
    readonly currentlyUpdatedItemMetadata!: Metadata;

    @metadata.State
    readonly project_metadata_list!: Array<Metadata>;

    private value = String(this.currentlyUpdatedItemMetadata.value);
    private project_metadata_list_possible_values: Array<ListValue> | null = [];

    mounted(): void {
        const values = this.project_metadata_list.find(
            ({ short_name }) => short_name === this.currentlyUpdatedItemMetadata.short_name
        )?.allowed_list_values;
        if (!values) {
            return;
        }
        this.project_metadata_list_possible_values = values;
    }
}
</script>
