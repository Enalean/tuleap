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
    <button
        role="button"
        class="tlp-button-primary tlp-button-mini"
        data-test="table-admin-button"
        ref="modal_trigger"
    >
        <i class="fa-solid fa-gear tlp-button-icon" aria-hidden="true"></i>
        {{ $gettext("Administration") }}
    </button>

    <approval-table-administration-modal
        v-if="modal_trigger"
        v-bind:trigger="modal_trigger"
        v-bind:table="table"
        v-bind:item="item"
        v-on:refresh-data="$emit('refresh-data')"
    />
</template>

<script setup lang="ts">
import type { ApprovableDocument, ApprovalTable, Item } from "../../../type";
import { ref } from "vue";
import ApprovalTableAdministrationModal from "./ApprovalTableAdministrationModal.vue";

defineProps<{
    table: ApprovalTable;
    item: Item & ApprovableDocument;
}>();

defineEmits<{
    (e: "refresh-data"): void;
}>();

const modal_trigger = ref<HTMLButtonElement>();
</script>
