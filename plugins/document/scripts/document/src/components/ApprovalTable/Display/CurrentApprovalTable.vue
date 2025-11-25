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
        v-if="item.user_can_write"
        class="tlp-button-primary admin-button"
        v-bind:href="getLinkToApprovalTableAdmin()"
        data-test="table-admin-button"
    >
        <i class="fa-solid fa-gear tlp-button-icon" aria-hidden="true"></i>
        {{ $gettext("Administration") }}
    </a>

    <div
        v-if="!item.is_approval_table_enabled"
        class="tlp-alert-info"
        data-test="table-not-available"
    >
        {{ $gettext("The approval table is not yet available") }}
    </div>
    <approval-table-details
        v-else-if="approval_table !== null"
        v-bind:table="approval_table"
        v-bind:item="item"
        v-bind:is_readonly="
            approval_table.id !== props.item.approval_table?.id || approval_table.is_closed
        "
    />
</template>

<script setup lang="ts">
import type { ApprovableDocument, ApprovalTable, Item } from "../../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT } from "../../../configuration-keys";
import ApprovalTableDetails from "./ApprovalTableDetails.vue";
import { ref, watch } from "vue";
import { getDocumentApprovalTable } from "../../../api/approval-table-rest-querier";

const props = defineProps<{
    item: Item & ApprovableDocument;
    version: number | null;
}>();

const emit = defineEmits<{
    (e: "error", message: string): void;
}>();

const approval_table = ref<ApprovalTable | null>(props.item.approval_table);

const project = strictInject(PROJECT);

function getLinkToApprovalTableAdmin(): string {
    return `/plugins/docman/?group_id=${project.id}&action=approval_create&id=${props.item.id}`;
}

watch(
    () => props.version,
    () => {
        const version = props.version ?? props.item.approval_table?.version_number;
        if (version === null || version === undefined) {
            approval_table.value = null;
            return;
        }
        getDocumentApprovalTable(props.item.id, version).match(
            (table) => {
                approval_table.value = table;
            },
            (fault) => {
                approval_table.value = null;
                emit("error", fault.toString());
            },
        );
    },
    { immediate: true },
);
</script>

<style scoped lang="scss">
.admin-button {
    margin-bottom: var(--tlp-medium-spacing);
}
</style>
