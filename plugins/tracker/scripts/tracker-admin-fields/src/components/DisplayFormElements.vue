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
            v-bind:column_wrapper="element"
        />
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isTextField(element.field)"
        >
            <field-text v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isStringField(element.field)"
        >
            <field-string v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isIntField(element.field)"
        >
            <field-int v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isFloatField(element.field)"
        >
            <field-float v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isDateField(element.field)"
        >
            <field-date v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isSelectField(element.field)"
        >
            <field-select v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isCheckbox(element.field)"
        >
            <field-checkbox v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isRadioButton(element.field)"
        >
            <field-radio v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isStaticText(element.field)"
        >
            <field-static-text v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isAnIdField(element.field)"
        >
            <field-id v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isStaticUserText(element.field)"
        >
            <field-static-user-text v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isStaticDateText(element.field)"
        >
            <field-static-date-text v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isLineSeparator(element.field)"
        >
            <line-separator v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isLineBreak(element.field)"
        >
            <line-break v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isAnArtifactLinkField(element.field)"
        >
            <field-link v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isAFileField(element.field)"
        >
            <field-file v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper
            v-bind:field_id="element.field.field_id"
            v-else-if="isCrossReferenceField(element.field)"
        >
            <field-cross-reference v-bind:field="element.field" />
        </draggable-wrapper>
        <draggable-wrapper v-bind:field_id="element.field.field_id" v-else>
            <base-field v-bind:field="element.field" />
        </draggable-wrapper>
    </template>
</template>

<script setup lang="ts">
import type { ElementWithChildren, Fieldset } from "../type";
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
    ARTIFACT_LINK_FIELD,
    SUBMITTED_BY_FIELD,
    LAST_UPDATED_BY_FIELD,
    SUBMISSION_DATE_FIELD,
    LAST_UPDATE_DATE_FIELD,
    CROSS_REFERENCE_FIELD,
    FILE_FIELD,
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
    ArtifactLinkFieldStructure,
    FileFieldStructure,
} from "@tuleap/plugin-tracker-rest-api-types";
import { isColumnWrapper } from "../helpers/is-column-wrapper";
import ContainerFieldset from "./FormElements/ContainerFieldset.vue";
import ContainerColumnWrapper from "./FormElements/ContainerColumnWrapper.vue";
import BaseField from "./FormElements/BaseField.vue";
import FieldText from "./FormElements/FieldText.vue";
import FieldString from "./FormElements/FieldString.vue";
import FieldSelect from "./FormElements/FieldSelect.vue";
import FieldInt from "./FormElements/FieldInt.vue";
import FieldFloat from "./FormElements/FieldFloat.vue";
import LineSeparator from "./FormElements/LineSeparator.vue";
import LineBreak from "./FormElements/LineBreak.vue";
import FieldDate from "./FormElements/FieldDate.vue";
import FieldCheckbox from "./FormElements/FieldCheckbox.vue";
import FieldRadio from "./FormElements/FieldRadio.vue";
import FieldStaticText from "./FormElements/FieldStaticText.vue";
import FieldId from "./FormElements/FieldId.vue";
import FieldLink from "./FormElements/FieldLink.vue";
import FieldStaticUserText from "./FormElements/FieldStaticUserText.vue";
import FieldStaticDateText from "./FormElements/FieldStaticDateText.vue";
import FieldCrossReference from "./FormElements/FieldCrossReference.vue";
import FieldFile from "./FormElements/FieldFile.vue";
import DraggableWrapper from "./DraggableWrapper.vue";

defineProps<{
    elements: ElementWithChildren["children"];
}>();

type TrackerElement = ElementWithChildren | ElementWithChildren["children"][0];

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
    return field.type === SELECTBOX_FIELD || field.type === MULTI_SELECTBOX_FIELD;
}

function isFieldset(element: TrackerElement): element is Fieldset {
    return "field" in element && element.field.type === CONTAINER_FIELDSET;
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

function isAnArtifactLinkField(field: StructureFields): field is ArtifactLinkFieldStructure {
    return field.type === ARTIFACT_LINK_FIELD;
}

function isAnIdField(field: StructureFields): boolean {
    return field.type === ARTIFACT_ID_FIELD || field.type === ARTIFACT_ID_IN_TRACKER_FIELD;
}

function isStaticUserText(field: StructureFields): boolean {
    return field.type === SUBMITTED_BY_FIELD || field.type === LAST_UPDATED_BY_FIELD;
}

function isStaticDateText(field: StructureFields): boolean {
    return field.type === SUBMISSION_DATE_FIELD || field.type === LAST_UPDATE_DATE_FIELD;
}

function isCrossReferenceField(field: StructureFields): boolean {
    return field.type === CROSS_REFERENCE_FIELD;
}

function isAFileField(field: StructureFields): field is FileFieldStructure {
    return field.type === FILE_FIELD;
}
</script>
