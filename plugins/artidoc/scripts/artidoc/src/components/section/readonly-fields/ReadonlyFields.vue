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
  -
  -->

<template>
    <div class="section-fields tlp-card tlp-card-inactive">
        <div
            v-for="(readonly_field, index) in section.fields"
            v-bind:key="index"
            class="tlp-property"
            v-bind:class="{ 'display-field-in-block': readonly_field.display_type === 'block' }"
        >
            <field-string
                v-if="isStringField(readonly_field)"
                v-bind:field_string="readonly_field"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import FieldString from "@/components/section/readonly-fields/FieldString.vue";
import { isStringField } from "@/sections/readonly-fields/ReadonlyFieldsCollection";
import type { SectionBasedOnArtifact } from "@/helpers/artidoc-section.type";

defineProps<{
    section: SectionBasedOnArtifact;
}>();
</script>

<style scoped lang="scss">
.section-fields {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    margin: var(--tlp-medium-spacing) 0 0;

    > .display-field-in-block {
        grid-column-start: span 4;
    }
}
</style>
