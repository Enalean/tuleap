<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
  -
  -->

<template>
    <cell-string v-if="is_string">{{ value_string }}</cell-string>
    <cell-string v-else-if="is_list">{{ get_value_list }}</cell-string>
    <cell-date v-else-if="is_date" v-bind:date="value_date" />
    <td v-else></td>
</template>

<script setup lang="ts">
import type {
    AdditionalFieldNumber,
    CustomPropertySearchResultList,
    CustomPropertySearchResultString,
    ItemSearchResult,
    CustomPropertySearchResult,
    CustomPropertySearchResultDate,
} from "../../../../type";
import CellString from "./CellString.vue";
import CellDate from "./CellDate.vue";
import { ref } from "vue";

const props = defineProps<{ item: ItemSearchResult; column_name: AdditionalFieldNumber }>();

const property = props.item.custom_properties[props.column_name] ?? null;

const value_date = ref(isDate(property) ? property.value : null);
const value_string = ref(isString(property) ? property.value : "");
const value_list = ref(isList(property) ? property.values.join(", ") : "");

const is_date = ref(isDate(property) && value_date.value !== null);
const is_string = ref(isString(property));
const is_list = ref(isList(property));

function isDate(
    property: CustomPropertySearchResult | null,
): property is CustomPropertySearchResultDate {
    return property !== null && property.type === "date";
}

function isString(
    property: CustomPropertySearchResult | null,
): property is CustomPropertySearchResultString {
    return property !== null && property.type === "string";
}

function isList(
    property: CustomPropertySearchResult | null,
): property is CustomPropertySearchResultList {
    return property !== null && property.type === "list";
}

defineExpose({ value_list, value_string, value_date });
</script>
