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
    <section v-if="item">
        <document-details-tabs v-bind:item="item" v-bind:active_tab="ApprovalTableTab" />

        <div class="tlp-framed-horizontally">
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header approval-table-header">
                        <h1 class="tlp-pane-title">
                            <i class="fa-regular fa-fw fa-square-check" aria-hidden="true"></i>
                            {{ $gettext("Approval table details") }}
                        </h1>

                        <approval-table-administration
                            v-if="
                                isAnApprovableDocument(item) &&
                                item.has_approval_table &&
                                item.user_can_write &&
                                item.approval_table !== null
                            "
                            v-bind:table="item.approval_table"
                            v-bind:item="item"
                            v-on:refresh-data="refreshData()"
                        />
                    </div>

                    <section class="tlp-pane-section">
                        <div v-if="error_message !== ''" class="tlp-alert-danger">
                            {{ error_message }}
                        </div>

                        <div
                            v-if="!isAnApprovableDocument(item)"
                            class="tlp-alert-danger"
                            data-test="error-not-approvable"
                        >
                            {{ $gettext("This item cannot have an approval table") }}
                        </div>

                        <no-approval-table
                            v-else-if="!item.has_approval_table"
                            v-bind:item="item"
                            v-on:table-created="refreshData()"
                        />

                        <current-approval-table
                            v-else
                            v-bind:item="item"
                            v-bind:version="version"
                            v-on:error="(message) => (error_message = message)"
                            v-on:refresh-data="refreshData()"
                        />
                    </section>
                </div>
            </section>

            <section class="tlp-pane" v-if="is_item_versionable">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">
                            <i class="fa-solid fa-fw fa-list" aria-hidden="true"></i>
                            {{ $gettext("Approval table history") }}
                        </h1>
                    </div>

                    <section class="tlp-pane-section">
                        <approval-table-history
                            v-if="isAnApprovableDocument(item)"
                            v-bind:item="item"
                            v-bind:version="version"
                            v-on:error="(message) => (error_message = message)"
                        />
                    </section>
                </div>
            </section>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, ref } from "vue";
import type { Item } from "../../type";
import { useActions } from "vuex-composition-helpers";
import DocumentDetailsTabs from "../Folder/DocumentDetailsTabs.vue";
import { ApprovalTableTab } from "../../helpers/details-tabs";
import { isAnApprovableDocument, isItemVersionable } from "../../helpers/approval-table-helper";
import NoApprovalTable from "./Creation/NoApprovalTable.vue";
import CurrentApprovalTable from "./Display/CurrentApprovalTable.vue";
import ApprovalTableHistory from "./History/ApprovalTableHistory.vue";
import ApprovalTableAdministration from "./Administration/ApprovalTableAdministration.vue";

const props = defineProps<{
    item_id: number;
    version: number | null;
}>();

const item = ref<Item | null>(null);
const error_message = ref("");

const is_item_versionable = computed(() => item.value && isItemVersionable(item.value));

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);

onBeforeMount(async () => {
    item.value = await loadDocumentWithAscendentHierarchy(props.item_id);
});

async function refreshData(): Promise<void> {
    item.value = await loadDocumentWithAscendentHierarchy(props.item_id);
}
</script>

<style scoped lang="scss">
.approval-table-header {
    display: flex;
    justify-content: space-between;
}
</style>
