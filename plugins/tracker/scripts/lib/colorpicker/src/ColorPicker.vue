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
        <a
            class="dropdown-toggle"
            href="#"
            data-target="#"
            data-toggle="dropdown"
            v-on:click.prevent="showRightPalette"
        >
            <old-color-picker-preview v-if="show_old_preview" v-bind:color="color" />
            <color-picker-preview v-else v-bind:color="color" />
        </a>

        <div class="dropdown-menu" role="menu">
            <color-picker-palette
                v-if="!is_old_palette_shown"
                v-on:color-update="setColor"
                v-bind:current_color="color"
            />

            <old-color-picker-palette v-if="is_old_palette_shown" v-on:color-update="setColor" />

            <!-- Set transparent when clicked -->
            <p v-if="is_old_palette_shown" class="old-color-preview">
                <old-color-picker-preview
                    v-bind:color="color"
                    class="colorpicker-transparent-preview"
                    v-on:color-update="setColor"
                />
                <span class="old-colorpicker-no-color-label" v-on:click="setColor()">
                    {{ $gettext("No color") }}
                </span>
            </p>

            <color-picker-switch
                v-if="is_old_palette_enabled"
                v-bind:is_switch_disabled="is_switch_disabled"
                v-bind:is_old_palette_shown="is_old_palette_shown"
                v-on:switch-palette="switchPalettes"
            />
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
import ColorPickerSwitch from "./ColorPickerSwitch.vue";

import OldColorPickerPreview from "./OldColorPickerPreview.vue";
import OldColorPickerPalette from "./OldColorPickerPalette.vue";
import ColorPickerPreview from "./ColorPickerPreview.vue";
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{
    input_name: string;
    input_id: string;
    current_color: string;
    is_switch_disabled: boolean;
    is_old_palette_enabled: boolean;
}>();

const color = ref<string>(props.current_color);

const is_hexa_color = computed((): boolean => color.value.includes("#"));
const show_old_preview = computed((): boolean => color.value.length === 0 || is_hexa_color.value);
const is_old_palette_shown = ref<boolean>(
    props.is_old_palette_enabled && is_hexa_color.value && !props.is_switch_disabled,
);

function setColor(new_color = ""): void {
    color.value = new_color;
}

function switchPalettes(): void {
    is_old_palette_shown.value = props.is_old_palette_enabled && !is_old_palette_shown.value;
}
function showRightPalette(): void {
    is_old_palette_shown.value =
        props.is_old_palette_enabled && is_hexa_color.value && !props.is_switch_disabled;
}
</script>
