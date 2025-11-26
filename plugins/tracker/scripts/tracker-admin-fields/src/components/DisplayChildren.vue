<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
    <template v-for="(child, index) of children" v-bind:key="index">
        <container-fieldset v-if="isFieldset(child)" v-bind:fieldset="child" />
        <container-column-wrapper v-if="isColumnWrapper(child)" v-bind:columns="child.columns" />
        <base-field v-else v-bind:field="child.field" />
    </template>
</template>

<script setup lang="ts">
import type { ColumnWrapper, ElementWithChildren, Fieldset } from "../type";
import { CONTAINER_FIELDSET } from "@tuleap/plugin-tracker-constants";
import ContainerFieldset from "./FormElements/ContainerFieldset.vue";
import ContainerColumnWrapper from "./FormElements/ContainerColumnWrapper.vue";
import BaseField from "./FormElements/BaseField.vue";

defineProps<{
    children: ElementWithChildren["children"];
}>();

type Element = ElementWithChildren | ElementWithChildren["children"][0];

function isFieldset(element: Element): element is Fieldset {
    return "field" in element && element.field.type === CONTAINER_FIELDSET;
}

function isColumnWrapper(element: Element): element is ColumnWrapper {
    return "columns" in element;
}
</script>
