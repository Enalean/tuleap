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
    <div
        class="colorpicker-switch"
        v-bind:class="{ 'colorpicker-switch-to-old-palette': !is_old_palette_shown }"
    >
        <a
            v-bind:class="{ 'colorpicker-switch-disabled': is_switch_disabled }"
            v-bind:title="switch_title"
            v-on:click.prevent="switchPalette"
            href="#"
        >
            <i class="fa fa-random"></i>
            <span v-if="is_old_palette_shown">{{ $gettext("Switch to default colors") }}</span>
            <span v-else>{{ $gettext("Switch to old colors") }}</span>
        </a>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { computed } from "vue";

const props = defineProps<{ is_switch_disabled: boolean; is_old_palette_shown: boolean }>();

const { $gettext } = useGettext();

const switch_title = computed((): string =>
    props.is_switch_disabled
        ? $gettext(
              "You can't switch to old colors because the field is currently being used by the card background color semantic",
          )
        : "",
);

const emit = defineEmits<{
    (e: "switch-palette"): void;
}>();

function switchPalette(event: MouseEvent): void {
    event.stopPropagation();

    if (props.is_switch_disabled) {
        return;
    }

    emit("switch-palette");
}
</script>
