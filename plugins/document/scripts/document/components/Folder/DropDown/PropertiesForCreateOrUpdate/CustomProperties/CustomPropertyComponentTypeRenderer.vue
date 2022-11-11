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
    <custom-property-text
        v-if="itemProperty.type === 'text'"
        v-bind:currently-updated-item-property="itemProperty"
        v-bind:value="itemProperty.value"
        data-test="document-custom-property-text"
    />
    <custom-property-string
        v-else-if="itemProperty.type === 'string'"
        v-bind:currently-updated-item-property="itemProperty"
        v-bind:value="itemProperty.value"
        data-test="document-custom-property-string"
    />
    <custom-property-list-single-value
        v-else-if="itemProperty.type === 'list' && !itemProperty.is_multiple_value_allowed"
        v-bind:value="itemProperty.value"
        v-bind:currently-updated-item-property="itemProperty"
        data-test="document-custom-property-list-single"
    />
    <custom-property-list-multiple-value
        v-else-if="itemProperty.type === 'list' && itemProperty.is_multiple_value_allowed"
        v-bind:currently-updated-item-property="itemProperty"
        data-test="document-custom-property-list-multiple"
    />
    <custom-property-date
        v-else-if="itemProperty.type === 'date'"
        v-bind:currently-updated-item-property="itemProperty"
        data-test="document-custom-property-date"
    />
</template>
<script setup lang="ts">
import CustomPropertyDate from "./CustomPropertyDate.vue";
import CustomPropertyListMultipleValue from "./CustomPropertyListMultipleValue.vue";
import CustomPropertyListSingleValue from "./CustomPropertyListSingleValue.vue";
import CustomPropertyString from "./CustomPropertyString.vue";
import CustomPropertyText from "./CustomPropertyText.vue";
import type { Property } from "../../../../../type";

defineProps<{ itemProperty: Property }>();
</script>
