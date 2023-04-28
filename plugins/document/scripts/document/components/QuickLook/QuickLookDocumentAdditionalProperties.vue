<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="tlp-property">
        <label v-bind:for="property_label" class="tlp-label" data-test="properties-list-label">
            {{ property_name }}
        </label>
        <p v-bind:id="property_label">
            <quick-look-property-date v-if="isDate" v-bind:property="property" />
            <template v-else-if="isList">
                <ul v-if="isMultipleList">
                    <li v-for="value in property.list_value" v-bind:key="value.id">
                        {{ value.name }}
                    </li>
                </ul>
                <template v-else-if="isSingleList">
                    {{ property.list_value[0].name }}
                </template>
            </template>

            <span class="document-quick-look-property-empty" v-else-if="!has_property_a_value">
                {{ $gettext("Empty") }}
            </span>
            <template v-else>
                <div v-dompurify-html="get_value"></div>
            </template>
        </p>
    </div>
</template>

<script setup lang="ts">
import { PROPERTY_OBSOLESCENCE_DATE_SHORT_NAME } from "../../constants";
import QuickLookPropertyDate from "./QuickLookPropertyDate.vue";
import { computed } from "vue";
import type { Property } from "../../type";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{ property: Property }>();

const PROPERTY_LIST_TYPE = "list";
const PROPERTY_DATE_TYPE = "date";

const property_label = computed((): string => {
    return `document-${props.property.short_name}`;
});

const isPropertyObsolesenceDate = computed((): boolean => {
    return props.property.short_name === PROPERTY_OBSOLESCENCE_DATE_SHORT_NAME;
});

const isDate = computed((): boolean => {
    return props.property.type === PROPERTY_DATE_TYPE;
});

const isList = computed((): boolean => {
    return props.property.type === PROPERTY_LIST_TYPE;
});

const isMultipleList = computed((): boolean => {
    return (
        isList.value && props.property.list_value !== null && props.property.list_value.length > 1
    );
});

const isSingleList = computed((): boolean => {
    return (
        isList.value && props.property.list_value !== null && props.property.list_value.length === 1
    );
});

const property_name = computed((): string => {
    if (isPropertyObsolesenceDate.value) {
        return $gettext("Validity");
    }
    return props.property.name;
});

const has_property_a_value = computed((): boolean => {
    if (isList.value) {
        return isMultipleList.value || isSingleList.value;
    }

    return props.property.value !== null && props.property.value !== "";
});

const get_value = computed((): string => {
    return props.property.post_processed_value ? props.property.post_processed_value : "";
});

defineExpose({
    get_value,
});
</script>
