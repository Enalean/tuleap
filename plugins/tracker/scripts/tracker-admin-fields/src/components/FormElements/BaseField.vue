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
    <div class="tlp-form-element">
        <template v-if="does_external_component_exists">
            <label-for-field v-bind:field="field" />
            <component v-bind:is="component_name" />
        </template>
        <label v-else class="tlp-label">{{ field.label }}</label>
    </div>
</template>

<script setup lang="ts">
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import LabelForField from "./LabelForField.vue";

const props = defineProps<{
    field: StructureFields;
}>();

const component_name = `tuleap-field-${props.field.type}`;
const does_external_component_exists = customElements.get(component_name) !== undefined;
</script>
