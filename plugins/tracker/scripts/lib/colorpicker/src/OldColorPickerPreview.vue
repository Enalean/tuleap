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
    <img
        class="old-color-picker-preview"
        v-bind:src="preview_image"
        v-bind:style="{ 'background-color': color }"
        v-on:click="setTransparent"
        v-bind:title="color"
    />
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{ color: string }>();

const preview_image = computed((): string => {
    const base_url = "/themes/FlamingParrot/images/";

    if (props.color.length) {
        return base_url + "blank16x16.png";
    }

    return base_url + "ic/layer-transparent.png";
});

const emit = defineEmits<{
    (e: "color-update", value: string): void;
}>();

function setTransparent(): void {
    if (props.color.length) {
        return;
    }

    emit("color-update", "");
}
</script>
