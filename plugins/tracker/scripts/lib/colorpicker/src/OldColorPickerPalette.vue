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
    <table class="old-colorpicker-palette">
        <old-color-picker-palette-row
            v-for="(shades, index) in colors"
            v-bind:shades="shades"
            v-bind:key="index"
            v-on:color-update="colorChanged"
        />
    </table>
</template>

<script setup lang="ts">
import colors from "./old-color-palette.js";
import OldColorPickerPaletteRow from "./OldColorPickerPaletteRow.vue";

const emit = defineEmits<{
    (e: "color-update", value: string): void;
}>();

function colorChanged(color: string): void {
    emit("color-update", color);
}
</script>
