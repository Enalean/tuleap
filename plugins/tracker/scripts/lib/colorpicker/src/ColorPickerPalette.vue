<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="colorpicker-palette">
        <color-picker-palette-row
            v-for="(colors, index) in tlp_colors"
            v-bind:colors="colors"
            v-bind:selected_color="current_color"
            v-bind:key="index"
            v-on:color-update="updateColor"
        />
        <color-picker-no-color-option
            v-bind:selected_color="current_color"
            v-on:color-update="updateColor"
        />
    </div>
</template>

<script setup lang="ts">
import tlp_colors from "./new-color-palette.js";
import ColorPickerPaletteRow from "./ColorPickerPaletteRow.vue";
import ColorPickerNoColorOption from "./ColorPickerNoColorOption.vue";

defineProps<{ current_color: string }>();

const emit = defineEmits<{
    (e: "color-update", value: string): void;
}>();

function updateColor(color: string): void {
    emit("color-update", color);
}
</script>
