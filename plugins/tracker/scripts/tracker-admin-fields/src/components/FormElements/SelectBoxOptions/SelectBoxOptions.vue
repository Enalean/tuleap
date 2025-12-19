<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <option v-if="!field.required" v-bind:value="NONE_VALUE" data-test="none-value">
        {{ $gettext("None") }}
    </option>
    <option
        v-for="value in usable_values"
        v-bind:key="value.id"
        v-bind:value="value.id"
        v-bind:selected="isDefaultValue(value)"
        v-bind:data-color-value="
            isStaticListValue(value) && value.value_color ? value.value_color : undefined
        "
        v-bind:data-avatar-url="
            isUserBoundListValue(value) ? value.user_reference.avatar_url : undefined
        "
    >
        {{ getValue(value) }}
    </option>
</template>

<script setup lang="ts">
import type { ListFieldItem, ListFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import {
    isStaticListValue,
    isUserBoundListValue,
    NONE_VALUE,
} from "../../../helpers/list-field-value";

const props = defineProps<{
    field: ListFieldStructure;
}>();

const isDefaultValue = (value: ListFieldItem): boolean => {
    if (props.field.default_value.length === 0) {
        return false;
    }

    return Boolean(
        props.field.default_value.find((default_value) => {
            if (isStaticListValue(value)) {
                return default_value.id === value.id;
            }

            if (isUserBoundListValue(value)) {
                return default_value.id === value.user_reference.id;
            }

            return default_value.id === value.ugroup_reference.id;
        }),
    );
};

const usable_values = props.field.values.filter((value) => {
    return !(isStaticListValue(value) && value.is_hidden);
});

const getValue = (value: ListFieldItem): string => {
    if (isStaticListValue(value)) {
        return value.label;
    }

    if (isUserBoundListValue(value)) {
        return value.user_reference.real_name;
    }

    return value.ugroup_reference.label;
};
</script>
