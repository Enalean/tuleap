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
    <div class="tlp-form-element tlp-form-element-disabled" v-if="currentlyUpdatedItemMetadata.type === 'date'" data-test="document-custom-metadata-date">
        <label class="tlp-label"
               v-bind:for="`document-${currentlyUpdatedItemMetadata.short_name}`"
        >
            {{ currentlyUpdatedItemMetadata.name }}
            <i class="fa fa-asterisk" v-if="currentlyUpdatedItemMetadata.is_required"
               data-test="document-custom-metadata-is-required"
            >
            </i>
        </label>
        <div class="tlp-form-element tlp-form-element-prepend">
            <span class="tlp-prepend"><i class="fa fa-calendar"></i></span>
            <input
                type="text"
                v-bind:id="`document-${currentlyUpdatedItemMetadata.short_name}`"
                class="tlp-input tlp-input-date"
                size="12"
                ref="document_date_input"
                data-test="document-date-input"
                v-bind:required="currentlyUpdatedItemMetadata.is_required"
                v-on:click="currentlyUpdatedItemMetadata.value = $event.target.value"
                disabled
            >
        </div>
    </div>
</template>

<script>
import { datePicker } from "tlp";

export default {
    name: "CustomMetadataDate",
    props: {
        currentlyUpdatedItemMetadata: Object
    },
    mounted() {
        datePicker(this.$refs.document_date_input);

        if (
            this.currentlyUpdatedItemMetadata.value &&
            this.currentlyUpdatedItemMetadata.type === "date"
        ) {
            this.$refs.document_date_input.value = this.currentlyUpdatedItemMetadata.value;
        }
    }
};
</script>
