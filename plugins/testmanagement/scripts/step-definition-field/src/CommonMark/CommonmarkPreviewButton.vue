<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
  -
  -->

<template>
    <button
        class="tlp-button-secondary tlp-button-outline tlp-button-small button-commonmark-preview"
        type="button"
        v-on:click="$emit('commonmark-preview-event')"
        v-bind:disabled="is_preview_loading"
        data-test="button-commonmark-preview"
    >
        <i
            class="fa-solid"
            v-bind:class="{
                'fa-circle-notch fa-spin': is_preview_loading,
                'fa-eye': !is_in_preview_mode && !is_preview_loading,
                'fa-pencil-alt': is_in_preview_mode && !is_preview_loading,
            }"
            data-test="button-commonmark-preview-icon"
            aria-hidden="true"
        ></i>
        {{ button_preview_label }}
    </button>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{
    is_in_preview_mode: boolean;
    is_preview_loading: boolean;
}>();

defineEmits<{
    (e: "commonmark-preview-event"): void;
}>();

const button_preview_label = computed(() =>
    props.is_in_preview_mode ? $gettext("Edit") : $gettext("Preview"),
);
</script>
