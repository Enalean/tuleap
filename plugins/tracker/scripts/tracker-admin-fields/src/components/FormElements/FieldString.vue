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
        <label-for-field v-bind:id="id" v-bind:field="field" />
        <input class="tlp-input" v-bind:id="id" />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import LabelForField from "./LabelForField.vue";

const props = defineProps<{
    field: StructureFields;
}>();

const id = computed(() => "string-" + props.field.field_id);
</script>
