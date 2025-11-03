<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <i
        v-if="is_unsupported_color && is_hexa_color"
        class="fa-solid fa-triangle-exclamation text-error"
        v-bind:title="$gettext('This color is no longer supported, please select another one')"
        data-test="preview-unsupported-color"
    ></i>
    <img
        v-else-if="is_no_color"
        class="old-color-picker-preview"
        v-bind:src="transparent_layer"
        data-test="preview-no-color"
    />
    <span
        v-else
        class="color-picker-preview"
        v-bind:class="color_picker_class"
        v-bind:title="color"
        data-test="preview-color"
    ></span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { NO_COLOR } from "./colors";
import transparent_layer from "../assets/layer-transparent.png";

const props = defineProps<{
    color: string;
    is_unsupported_color: boolean;
}>();

const is_hexa_color = computed(() => props.color.includes("#"));
const is_no_color = computed(() => props.color === NO_COLOR);
const color_picker_class = computed(() => `color-picker-preview-${props.color}`);
</script>
