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
    <tr>
        <td
            v-for="shade in shades"
            class="colorpicker-squared-shade"
            v-bind:style="getStyle(shade)"
            v-bind:title="shade"
            v-bind:key="shade"
            v-on:click="updateColor(shade)"
        ></td>
    </tr>
</template>

<script setup lang="ts">
defineProps<{ shades: string[] }>();

function getStyle(shade: string): Record<string, string> {
    return {
        "background-color": shade,
        color: shade,
        background: shade,
    };
}

const emit = defineEmits<{
    (e: "color-update", value: string): void;
}>();

function updateColor(color: string): void {
    emit("color-update", color);
}
</script>
