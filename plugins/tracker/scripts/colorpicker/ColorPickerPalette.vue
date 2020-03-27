<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
        <template v-for="(colors, index) in tlp_colors">
            <color-picker-palette-row
                v-bind:colors="colors"
                v-bind:selected-color="currentColor"
                v-bind:key="index"
                v-on:color-update="updateColor"
            />
        </template>
        <color-picker-no-color-option
            v-bind:selected-color="currentColor"
            v-bind:no-color-label="noColorLabel"
            v-on:color-update="updateColor"
        />
    </div>
</template>

<script>
import tlp_colors from "./new-color-palette.js";
import ColorPickerPaletteRow from "./ColorPickerPaletteRow.vue";
import ColorPickerNoColorOption from "./ColorPickerNoColorOption.vue";

export default {
    name: "ColorPickerPalette",
    components: { ColorPickerPaletteRow, ColorPickerNoColorOption },
    props: {
        currentColor: String,
        noColorLabel: String,
    },
    data() {
        return {
            tlp_colors,
        };
    },
    methods: {
        updateColor(color) {
            this.$emit("color-update", color);
        },
    },
};
</script>
