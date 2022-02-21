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
        v-if="currentlyUpdatedItemMetadata.type === 'text'"
        data-test="document-custom-metadata-text"
    >
        <label
            class="tlp-label"
            v-bind:for="`document-{{currentlyUpdatedItemMetadata.short_name}}`"
        >
            {{ currentlyUpdatedItemMetadata.name }}
            <i
                class="fa fa-asterisk"
                v-if="currentlyUpdatedItemMetadata.is_required"
                data-test="document-custom-metadata-is-required"
            ></i>
        </label>
        <textarea
            class="tlp-textarea tlp-form-element"
            data-test="document-text-input"
            v-bind:id="`document-{{currentlyUpdatedItemMetadata.short_name}}`"
            v-bind:required="currentlyUpdatedItemMetadata.is_required"
            v-model="value"
            v-on:input="$emit('input', value)"
        ></textarea>
    </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Metadata } from "../../../../../store/metadata/module";

@Component
export default class CustomMetadataText extends Vue {
    @Prop({ required: true })
    readonly currentlyUpdatedItemMetadata!: Metadata;

    private value = String(this.currentlyUpdatedItemMetadata.value);
}
</script>
