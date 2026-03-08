<!--
  - Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
    <div class="tlp-property">
        <label class="tlp-label">{{ $gettext("Id") }}</label>
        <p>{{ field.field_id }}</p>
    </div>

    <div class="tlp-form-element">
        <label class="tlp-label" for="field-label">{{ $gettext("Label") }}</label>
        <input type="text" class="tlp-input" id="field-label" v-bind:value="field.label" />
    </div>

    <div class="tlp-form-element">
        <label class="tlp-label" for="field-name">{{ $gettext("Name") }}</label>
        <input type="text" class="tlp-input" id="field-name" v-bind:value="field.name" />
    </div>

    <div class="tlp-form-element">
        <label class="tlp-label" for="field-description">{{ $gettext("Description") }}</label>
        <textarea
            class="tlp-textarea"
            id="field-description"
            v-bind:value="field.description"
            rows="3"
        ></textarea>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";

const { $gettext } = useGettext();

defineProps<{ field: StructureFields }>();
</script>

<style lang="scss" scoped>
textarea {
    min-height: 3rem;
    resize: vertical;
}
</style>
