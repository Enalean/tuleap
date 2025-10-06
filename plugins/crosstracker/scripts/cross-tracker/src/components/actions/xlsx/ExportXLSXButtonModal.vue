<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
    <button
        type="button"
        class="tlp-button-primary tlp-button-mini tlp-button-outline"
        v-on:click="handleClick()"
        data-test="export-xlsx-button"
    >
        <i
            aria-hidden="true"
            class="tlp-button-icon fa-solid fa-download"
            data-test="export-xlsx-button-icon"
        ></i>
        {{ $gettext("Export XLSX") }}
    </button>
    <export-x-l-s-x-modal v-bind:current_query="current_query" />
</template>

<script setup lang="ts">
import { EMITTER } from "../../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    DISPLAY_XLSX_MODAL_EVENT,
    STARTING_XLSX_EXPORT_EVENT,
} from "../../../helpers/widget-events";
import ExportXLSXModal from "./ExportXLSXModal.vue";
import type { Query } from "../../../type";

const emitter = strictInject(EMITTER);

function handleClick(): void {
    emitter.emit(STARTING_XLSX_EXPORT_EVENT);
    emitter.emit(DISPLAY_XLSX_MODAL_EVENT);
}

defineProps<{
    current_query: Query;
}>();
</script>
