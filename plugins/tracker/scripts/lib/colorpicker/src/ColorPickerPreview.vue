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
    <i
        v-bind:title="$gettext('This color is no longer supported, please select another one')"
        class="fa-solid fa-triangle-exclamation text-error"
        v-if="is_unsupported_color && is_hexa_color"
    ></i>
    <img class="old-color-picker-preview" v-bind:src="src" v-else-if="is_no_color" />
    <span
        v-else
        class="color-picker-preview"
        v-bind:class="'colorpicker-preview-' + color"
        v-bind:title="color"
    ></span>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{ color: string; is_unsupported_color: boolean }>();
const is_hexa_color = computed((): boolean => props.color.includes("#"));
const src = "/themes/FlamingParrot/images/ic/layer-transparent.png";

const is_no_color = computed((): boolean => {
    return props.color.length === 0;
});
</script>
