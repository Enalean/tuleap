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
    <div>
        <div v-for="item_metadata in currentlyUpdatedItem.metadata"
             v-bind:key="item_metadata.short_name"
             class="document-metadata-properties-margin"
        >
            <custom-metadata-text
                v-if="item_metadata.type === 'text'"
                v-bind:currently-updated-item-metadata="item_metadata"
                data-test="document-custom-metadata-text"
            />
            <custom-metadata-string
                v-else-if="item_metadata.type === 'string'"
                v-bind:currently-updated-item-metadata="item_metadata"
                data-test="document-custom-metadata-string"
            />
            <custom-metadata-list-single-value
                v-else-if="item_metadata.type === 'list' && ! item_metadata.is_multiple_value_allowed"
                v-bind:currently-updated-item-metadata="item_metadata"
                data-test="document-custom-metadata-list-single"
            />
            <custom-metadata-list-multiple-value
                v-else-if="item_metadata.type === 'list' && item_metadata.is_multiple_value_allowed"
                v-bind:currently-updated-item-metadata="item_metadata"
                data-test="document-custom-metadata-list-multiple"
            />
            <custom-metadata-date
                v-else-if="item_metadata.type === 'date'"
                v-bind:currently-updated-item-metadata="item_metadata"
                data-test="document-custom-metadata-date"
            />
        </div>
    </div>
</template>

<script>
import CustomMetadataText from "./CustomMetadataText.vue";
import CustomMetadataString from "./CustomMetadataString.vue";
import CustomMetadataListSingleValue from "./CustomMetadataListSingleValue.vue";
import CustomMetadataListMultipleValue from "./CustomMetadataListMultipleValue.vue";
import CustomMetadataDate from "./CustomMetadataDate.vue";

export default {
    name: "CustomMetadata",
    components: {
        CustomMetadataDate,
        CustomMetadataListMultipleValue,
        CustomMetadataListSingleValue,
        CustomMetadataString,
        CustomMetadataText
    },
    props: {
        currentlyUpdatedItem: Object
    }
};
</script>
