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
    <div class="dropdown tracker-colorpicker">
        <a class="dropdown-toggle" href="#" data-target="#" data-toggle="dropdown">
            <color-picker-preview
                v-bind:color="color"
                v-bind:is_unsupported_color="is_unsupported_color"
            />
        </a>

        <div class="dropdown-menu" role="menu">
            <color-picker-palette v-on:color-update="setColor" v-bind:current_color="color" />
        </div>
        <input
            class="colorpicker-input"
            v-bind:id="input_id"
            v-bind:name="input_name"
            v-bind:value="color"
            type="hidden"
            size="6"
            autocomplete="off"
        />
    </div>
</template>

<script setup lang="ts">
import ColorPickerPalette from "./ColorPickerPalette.vue";

import ColorPickerPreview from "./ColorPickerPreview.vue";
import { ref } from "vue";

const props = defineProps<{
    input_name: string;
    input_id: string;
    current_color: string;
    is_unsupported_color: boolean;
}>();

const color = ref<string>(props.current_color);

function setColor(new_color = ""): void {
    color.value = new_color;
}
</script>
