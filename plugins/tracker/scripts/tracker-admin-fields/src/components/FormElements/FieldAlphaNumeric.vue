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
        <label-for-field v-bind:field="field" />
        <input
            type="text"
            class="tlp-input"
            v-bind:size="
                field.specific_properties.size > 0 ? field.specific_properties.size : undefined
            "
            v-bind:maxlength="
                field.specific_properties.maxchars > 0
                    ? field.specific_properties.maxchars
                    : undefined
            "
            v-bind:value="field.specific_properties.default_value"
            autocomplete="off"
        />
    </div>
</template>

<script setup lang="ts">
import type {
    FloatFieldStructure,
    IntFieldStructure,
    StringFieldStructure,
} from "@tuleap/plugin-tracker-rest-api-types";
import LabelForField from "./LabelForField.vue";

defineProps<{
    field: FloatFieldStructure | IntFieldStructure | StringFieldStructure;
}>();
</script>
