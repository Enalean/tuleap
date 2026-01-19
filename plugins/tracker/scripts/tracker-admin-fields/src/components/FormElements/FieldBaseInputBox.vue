<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label-for-field v-bind:field="field" />
        <label
            class="tlp-label"
            v-bind:class="[
                getInputTypeClass(),
                {
                    'input-box-with-badge-container': hasBadge(value),
                },
            ]"
            v-for="value in listFieldValue(field)"
            v-bind:key="value.id"
            v-bind:for="buildValueId(value)"
            data-test="input-box-label"
        >
            <input
                v-bind:type="getInputType()"
                v-bind:id="buildValueId(value)"
                v-bind:value="value.id"
                data-test="input-box-field-input"
                v-bind:name="field.name"
                v-bind:checked="isDefaultValue(value)"
            />
            <user-badge v-if="isUserBoundListValue(value)" v-bind:value="value" />
            <color-badge v-if="isStaticListValue(value)" v-bind:value="value" />
            {{ value.label }}
        </label>
    </div>
</template>

<script setup lang="ts">
import type { ListFieldItem, ListFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import {
    listFieldValue,
    isStaticListValue,
    isUserBoundListValue,
} from "../../helpers/list-field-value";
import LabelForField from "./LabelForField.vue";
import type {
    CheckBoxFieldIdentifier,
    RadioButtonFieldIdentifier,
} from "@tuleap/plugin-tracker-constants";
import { CHECKBOX_FIELD } from "@tuleap/plugin-tracker-constants";
import ColorBadge from "./ColorBadge.vue";
import UserBadge from "./UserBadge.vue";
import { hasColorValue } from "../../helpers/color-helper";

const props = defineProps<{
    field: ListFieldStructure;
    input_box_type: RadioButtonFieldIdentifier | CheckBoxFieldIdentifier;
}>();

function hasBadge(value: ListFieldItem): boolean {
    return (isStaticListValue(value) && hasColorValue(value)) || isUserBoundListValue(value);
}

function getInputType(): string {
    if (props.input_box_type === CHECKBOX_FIELD) {
        return `checkbox`;
    }
    return `radio`;
}

function buildValueId(value: ListFieldItem): string {
    if (props.input_box_type === CHECKBOX_FIELD) {
        return `cb_${value.id}`;
    }
    return `rb_${value.id}`;
}

function getInputTypeClass(): string {
    if (props.input_box_type === CHECKBOX_FIELD) {
        return `tlp-checkbox`;
    }
    return `tlp-radio`;
}

function isDefaultValue(value: ListFieldItem): boolean {
    return props.field.default_value.some((default_value) => value.id === default_value.id);
}
</script>

<style scoped lang="scss">
.input-box-with-badge-container {
    display: flex;
    gap: calc(var(--tlp-small-spacing) * 0.5);
    align-items: center;
}
</style>
