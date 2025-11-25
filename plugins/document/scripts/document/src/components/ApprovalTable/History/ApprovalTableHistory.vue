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
    <table class="tlp-table">
        <thead>
            <tr>
                <th>{{ $gettext("Document version") }}</th>
                <th>{{ $gettext("Version label") }}</th>
                <th>{{ $gettext("Owner") }}</th>
                <th>{{ $gettext("Status") }}</th>
                <th>{{ $gettext("Start date") }}</th>
            </tr>
        </thead>

        <approval-table-history-loading v-if="approval_tables === null" />
        <tbody v-else>
            <tr v-if="approval_tables.length === 0">
                <td colspan="5" class="tlp-table-cell-empty">
                    {{ $gettext("There is no history") }}
                </td>
            </tr>
            <tr
                v-else
                v-for="table in approval_tables"
                v-bind:key="table.id"
                data-test="history-row"
            >
                <td data-test="history-row-number">
                    <router-link
                        v-if="shouldDisplayLinkToVersion(table)"
                        v-bind:to="{
                            name: 'approval-table',
                            params: { item_id: item.id, version: table.version_number },
                        }"
                    >
                        {{ table.version_number }}
                    </router-link>
                    <template v-else>
                        {{ table.version_number }}
                    </template>
                </td>
                <td data-test="history-row-label">{{ table.version_label }}</td>
                <td><user-badge v-bind:user="table.table_owner" /></td>
                <td>
                    <approval-badge
                        v-bind:approval_table="table"
                        v-bind:enabled="true"
                        v-bind:is-in-folder-content-row="false"
                    />
                </td>
                <td>
                    <document-relative-date
                        v-bind:date="table.approval_request_date"
                        relative_placement="right"
                    />
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup lang="ts">
import type { ApprovableDocument, ApprovalTable, Item } from "../../../type";
import { onBeforeMount, ref } from "vue";
import ApprovalTableHistoryLoading from "./ApprovalTableHistoryLoading.vue";
import { getAllDocumentApprovalTables } from "../../../api/approval-table-rest-querier";
import UserBadge from "../../User/UserBadge.vue";
import ApprovalBadge from "../../Folder/ApprovalTables/ApprovalBadge.vue";
import DocumentRelativeDate from "../../Date/DocumentRelativeDate.vue";

const props = defineProps<{
    item: Item & ApprovableDocument;
    version: number | null;
}>();

const emit = defineEmits<{
    (e: "error", message: string): void;
}>();

const approval_tables = ref<ReadonlyArray<ApprovalTable> | null>(null);

onBeforeMount(() => {
    getAllDocumentApprovalTables(props.item.id).match(
        (tables) => {
            approval_tables.value = tables;
        },
        (fault) => {
            emit("error", fault.toString());
        },
    );
});

function shouldDisplayLinkToVersion(table: ApprovalTable): boolean {
    if (props.version !== null) {
        return props.version !== table.version_number;
    }
    return props.item.approval_table?.version_number !== table.version_number;
}
</script>
