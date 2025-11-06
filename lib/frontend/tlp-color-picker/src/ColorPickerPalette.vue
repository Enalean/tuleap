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
    <div class="color-picker-grid">
        <span
            v-for="color_name in COLOR_NAMES"
            v-bind:key="color_name"
            class="color-picker-circular-color"
            v-bind:class="[
                `color-picker-circular-color-${color_name}`,
                {
                    'fa-solid fa-check': current_color === color_name,
                },
            ]"
            v-bind:title="color_name"
            v-on:click="emit('color-update', color_name)"
            data-test="color-picker-item"
            v-bind:data-test-color="color_name"
        ></span>
        <div
            v-if="is_no_color_allowed"
            v-on:click="emit('color-update', NO_COLOR)"
            class="color-picker-row-no-color"
            data-test="color-picker-item"
            data-test-color="no-color"
        >
            <span
                class="color-picker-circular-color color-picker-circular-no-color"
                v-bind:class="{
                    'color-picker-no-color-selected fa-solid fa-check': current_color === NO_COLOR,
                }"
                v-bind:title="$gettext('No color')"
            ></span>
            <span>{{ $gettext("No color") }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { COLOR_NAMES } from "@tuleap/core-constants";
import { NO_COLOR } from "./colors";

defineProps<{
    current_color: string;
    is_no_color_allowed: boolean;
}>();

const emit = defineEmits<{
    (e: "color-update", value: string): void;
}>();
</script>
