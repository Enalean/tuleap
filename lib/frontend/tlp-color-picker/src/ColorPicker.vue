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
    <a
        ref="popover_trigger"
        data-placement="bottom"
        data-trigger="click"
        class="color-picker-preview-button"
    >
        <color-picker-preview
            v-bind:color="color"
            v-bind:is_unsupported_color="is_unsupported_color"
        />
    </a>

    <section ref="popover_content" class="tlp-popover color-picker-popover">
        <div class="tlp-popover-arrow"></div>
        <div class="tlp-popover-body">
            <color-picker-palette
                v-on:color-update="onColorUpdate"
                v-bind:current_color="color"
                v-bind:is_no_color_allowed="is_no_color_allowed || false"
            />
            <input
                v-bind:name="input_name"
                v-bind:id="input_id"
                v-bind:value="color"
                type="hidden"
                autocomplete="off"
            />
        </div>
    </section>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { createPopover } from "@tuleap/tlp-popovers";
import ColorPickerPreview from "./ColorPickerPreview.vue";
import ColorPickerPalette from "./ColorPickerPalette.vue";
import type { Popover } from "@tuleap/tlp-popovers";

const props = withDefaults(
    defineProps<{
        input_name: string;
        input_id: string;
        current_color: string;
        is_unsupported_color: boolean;
        on_color_change_callback?: (color: string) => void;
        is_no_color_allowed?: boolean;
    }>(),
    {
        on_color_change_callback: () => {},
        is_no_color_allowed: true,
    },
);

const popover_trigger = ref<HTMLElement>();
const popover_content = ref<HTMLElement>();
const color = ref<string>(props.current_color);
const popover_instance = ref<Popover>();

onMounted(() => {
    if (popover_trigger.value === undefined || popover_content.value === undefined) {
        throw Error("Failed to create popover");
    }

    popover_instance.value = createPopover(popover_trigger.value, popover_content.value);
});

onBeforeUnmount(() => {
    popover_instance.value?.destroy();
});

function onColorUpdate(new_color: string): void {
    color.value = new_color;
    popover_instance.value?.hide();
    props.on_color_change_callback(new_color);
}
</script>
