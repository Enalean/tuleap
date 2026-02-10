<!--
  - Copyright (c) Enalean, 2026-present. All Rights Reserved.
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
    <label-for-field v-bind:field="field" />
    <div class="tlp-form-element-append">
        <input
            type="text"
            class="tlp-input"
            placeholder="0"
            v-bind:value="field.specific_properties.default_value"
        />

        <button
            class="tlp-append tlp-button-primary tlp-button-outline"
            v-bind:aria-label="$gettext('Auto-compute')"
            disabled
        >
            <i class="fa-solid fa-rotate-right tlp-button-icon" aria-hidden="true"></i>
            {{ $gettext("Auto-compute") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import type { ComputedFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import LabelForField from "./LabelForField.vue";

defineProps<{
    field: ComputedFieldStructure;
}>();
</script>
