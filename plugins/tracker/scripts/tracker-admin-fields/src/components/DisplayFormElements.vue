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
        <field-text v-else-if="isTextField(element.field)" v-bind:field="element.field" />
        <field-string v-else-if="isStringField(element.field)" v-bind:field="element.field" />
        <field-int v-else-if="isIntField(element.field)" v-bind:field="element.field" />
        <field-float v-else-if="isFloatField(element.field)" v-bind:field="element.field" />
        <field-date v-else-if="isDateField(element.field)" v-bind:field="element.field" />
        <field-select v-else-if="isSelectField(element.field)" v-bind:field="element.field" />
        <field-multi-select
            v-else-if="isMultiSelectField(element.field)"
            v-bind:field="element.field"
        />
        <field-checkbox v-else-if="isCheckbox(element.field)" v-bind:field="element.field" />
        <field-radio v-else-if="isRadioButton(element.field)" v-bind:field="element.field" />
        <field-static-text v-else-if="isStaticText(element.field)" v-bind:field="element.field" />
        <field-id v-else-if="isAnIdField(element.field)" v-bind:field="element.field" />
        <line-separator v-else-if="isLineSeparator(element.field)" v-bind:field="element.field" />
        <line-break v-else-if="isLineBreak(element.field)" v-bind:field="element.field" />
        <base-field v-else v-bind:field="element.field" />
    </template>
</template>

<script setup lang="ts">
import type { ColumnWrapper, ElementWithChildren, Fieldset } from "../type";
import {
    CONTAINER_FIELDSET,
    DATE_FIELD,
    FLOAT_FIELD,
    INT_FIELD,
    LINE_BREAK,
    MULTI_SELECTBOX_FIELD,
    SELECTBOX_FIELD,
    SEPARATOR,
    STRING_FIELD,
    TEXT_FIELD,
    CHECKBOX_FIELD,
    RADIO_BUTTON_FIELD,
    ARTIFACT_ID_FIELD,
    ARTIFACT_ID_IN_TRACKER_FIELD,
    STATIC_RICH_TEXT,
} from "@tuleap/plugin-tracker-constants";
import type {
    IntFieldStructure,
    FloatFieldStructure,
    StringFieldStructure,
    TextFieldStructure,
    StructureFields,
    SeparatorStructure,
    LineBreakStructure,
    EditableDateFieldStructure,
    ListFieldStructure,
    StaticRichTextStructure,
} from "@tuleap/plugin-tracker-rest-api-types";
import ContainerFieldset from "./FormElements/ContainerFieldset.vue";
import ContainerColumnWrapper from "./FormElements/ContainerColumnWrapper.vue";
import BaseField from "./FormElements/BaseField.vue";
import FieldText from "./FormElements/FieldText.vue";
import FieldString from "./FormElements/FieldString.vue";
import FieldSelect from "./FormElements/FieldSelect.vue";
import FieldMultiSelect from "./FormElements/FieldMultiSelect.vue";
import FieldInt from "./FormElements/FieldInt.vue";
import FieldFloat from "./FormElements/FieldFloat.vue";
import LineSeparator from "./FormElements/LineSeparator.vue";
import LineBreak from "./FormElements/LineBreak.vue";
import FieldDate from "./FormElements/FieldDate.vue";
import FieldCheckbox from "./FormElements/FieldCheckbox.vue";
import FieldRadio from "./FormElements/FieldRadio.vue";
import FieldStaticText from "./FormElements/FieldStaticText.vue";
import FieldId from "./FormElements/FieldId.vue";

defineProps<{
    elements: ElementWithChildren["children"];
}>();

type Element = ElementWithChildren | ElementWithChildren["children"][0];

function isDateField(field: StructureFields): field is EditableDateFieldStructure {
    return field.type === DATE_FIELD;
}

function isTextField(field: StructureFields): field is TextFieldStructure {
    return field.type === TEXT_FIELD;
}

function isIntField(field: StructureFields): field is IntFieldStructure {
    return field.type === INT_FIELD;
}

function isFloatField(field: StructureFields): field is FloatFieldStructure {
    return field.type === FLOAT_FIELD;
}

function isStringField(field: StructureFields): field is StringFieldStructure {
    return field.type === STRING_FIELD;
}

function isSelectField(field: StructureFields): field is ListFieldStructure {
    return field.type === SELECTBOX_FIELD;
}

function isMultiSelectField(field: StructureFields): field is ListFieldStructure {
    return field.type === MULTI_SELECTBOX_FIELD;
}

function isFieldset(element: Element): element is Fieldset {
    return "field" in element && element.field.type === CONTAINER_FIELDSET;
}

function isColumnWrapper(element: Element): element is ColumnWrapper {
    return "columns" in element;
}

function isLineSeparator(field: StructureFields): field is SeparatorStructure {
    return field.type === SEPARATOR;
}

function isLineBreak(field: StructureFields): field is LineBreakStructure {
    return field.type === LINE_BREAK;
}

function isCheckbox(field: StructureFields): field is ListFieldStructure {
    return field.type === CHECKBOX_FIELD;
}

function isRadioButton(field: StructureFields): field is ListFieldStructure {
    return field.type === RADIO_BUTTON_FIELD;
}

function isStaticText(field: StructureFields): field is StaticRichTextStructure {
    return field.type === STATIC_RICH_TEXT;
}

function isAnIdField(field: StructureFields): boolean {
    return field.type === ARTIFACT_ID_FIELD || field.type === ARTIFACT_ID_IN_TRACKER_FIELD;
}
</script>
