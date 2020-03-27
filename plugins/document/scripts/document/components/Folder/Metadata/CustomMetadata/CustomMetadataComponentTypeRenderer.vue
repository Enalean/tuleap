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
    <custom-metadata-text
        v-if="itemMetadata.type === 'text'"
        v-bind:currently-updated-item-metadata="itemMetadata"
        data-test="document-custom-metadata-text"
    />
    <custom-metadata-string
        v-else-if="itemMetadata.type === 'string'"
        v-bind:currently-updated-item-metadata="itemMetadata"
        data-test="document-custom-metadata-string"
    />
    <custom-metadata-list-single-value
        v-else-if="itemMetadata.type === 'list' && !itemMetadata.is_multiple_value_allowed"
        v-bind:currently-updated-item-metadata="itemMetadata"
        data-test="document-custom-metadata-list-single"
    />
    <custom-metadata-list-multiple-value
        v-else-if="itemMetadata.type === 'list' && itemMetadata.is_multiple_value_allowed"
        v-bind:currently-updated-item-metadata="itemMetadata"
        data-test="document-custom-metadata-list-multiple"
    />
    <custom-metadata-date
        v-else-if="itemMetadata.type === 'date'"
        v-bind:currently-updated-item-metadata="itemMetadata"
        data-test="document-custom-metadata-date"
        v-model="itemMetadata.value"
    />
</template>
<script>
import CustomMetadataDate from "./CustomMetadataDate.vue";
import CustomMetadataListMultipleValue from "./CustomMetadataListMultipleValue.vue";
import CustomMetadataListSingleValue from "./CustomMetadataListSingleValue.vue";
import CustomMetadataString from "./CustomMetadataString.vue";
import CustomMetadataText from "./CustomMetadataText.vue";

export default {
    name: "CustomMetadataComponentTypeRenderer",
    components: {
        CustomMetadataDate,
        CustomMetadataListMultipleValue,
        CustomMetadataListSingleValue,
        CustomMetadataString,
        CustomMetadataText,
    },
    props: {
        itemMetadata: Object,
    },
};
</script>
