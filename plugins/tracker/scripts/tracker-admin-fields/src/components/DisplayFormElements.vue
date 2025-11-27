<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <template v-for="(element, index) of elements" v-bind:key="index">
        <container-fieldset v-if="isFieldset(element)" v-bind:fieldset="element" />
        <container-column-wrapper
            v-else-if="isColumnWrapper(element)"
            v-bind:columns="element.columns"
        />
        <field-text v-else-if="isTextField(element)" v-bind:field="element.field" />
        <field-string v-else-if="isStringField(element)" v-bind:field="element.field" />
        <field-select v-else-if="isSelectField(element)" v-bind:field="element.field" />
        <field-multi-select v-else-if="isMultiSelectField(element)" v-bind:field="element.field" />
        <base-field v-else v-bind:field="element.field" />
    </template>
</template>

<script setup lang="ts">
import type { ColumnWrapper, ElementWithChildren, Fieldset } from "../type";
import {
    CONTAINER_FIELDSET,
    FLOAT_FIELD,
    INT_FIELD,
    MULTI_SELECTBOX_FIELD,
    SELECTBOX_FIELD,
    STRING_FIELD,
    TEXT_FIELD,
} from "@tuleap/plugin-tracker-constants";
import ContainerFieldset from "./FormElements/ContainerFieldset.vue";
import ContainerColumnWrapper from "./FormElements/ContainerColumnWrapper.vue";
import BaseField from "./FormElements/BaseField.vue";
import FieldText from "./FormElements/FieldText.vue";
import FieldString from "./FormElements/FieldString.vue";
import FieldSelect from "./FormElements/FieldSelect.vue";
import FieldMultiSelect from "./FormElements/FieldMultiSelect.vue";

defineProps<{
    elements: ElementWithChildren["children"];
}>();

type Element = ElementWithChildren | ElementWithChildren["children"][0];

function isTextField(element: Element): boolean {
    return "field" in element && element.field.type === TEXT_FIELD;
}

function isStringField(element: Element): boolean {
    return (
        "field" in element &&
        (element.field.type === STRING_FIELD ||
            element.field.type === INT_FIELD ||
            element.field.type === FLOAT_FIELD)
    );
}

function isSelectField(element: Element): boolean {
    return "field" in element && element.field.type === SELECTBOX_FIELD;
}

function isMultiSelectField(element: Element): boolean {
    return "field" in element && element.field.type === MULTI_SELECTBOX_FIELD;
}

function isFieldset(element: Element): element is Fieldset {
    return "field" in element && element.field.type === CONTAINER_FIELDSET;
}

function isColumnWrapper(element: Element): element is ColumnWrapper {
    return "columns" in element;
}
</script>
