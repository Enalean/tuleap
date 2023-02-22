<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <span
        v-bind:class="approval_data.badge_class"
        class="document-approval-badge"
        v-if="hasAnApprovalTable()"
    >
        <i class="fa-solid tlp-badge-icon" v-bind:class="approval_data.icon_badge"></i>
        {{ approval_data.badge_label }}
    </span>
</template>

<script lang="ts">
import { extractApprovalTableData } from "../../../helpers/approval-table-helper";
import { APPROVAL_APPROVED, APPROVAL_NOT_YET, APPROVAL_REJECTED } from "../../../constants";
import { Component, Prop, Vue, Watch } from "vue-property-decorator";
import type { ApprovableDocument } from "../../../type";
import type { ApprovalTableBadge } from "../../../helpers/approval-table-helper";

@Component
export default class ApprovalBadge extends Vue {
    @Prop({ required: true })
    readonly item!: ApprovableDocument;

    @Prop({ required: true })
    readonly isInFolderContentRow!: boolean;

    private approval_data: ApprovalTableBadge | null = null;

    hasAnApprovalTable(): boolean {
        return this.item.approval_table !== null && this.approval_data !== null;
    }

    getTranslatedApprovalStatesMap(): Map<string, string> {
        const approval_states_map = new Map();

        approval_states_map.set(this.$gettext("Approved"), APPROVAL_APPROVED);
        approval_states_map.set(this.$gettext("Not yet"), APPROVAL_NOT_YET);
        approval_states_map.set(this.$gettext("Rejected"), APPROVAL_REJECTED);

        return approval_states_map;
    }

    mounted(): void {
        this.setApprovalData();
    }

    @Watch("item", { deep: true })
    setApprovalData(): void {
        if (!this.item.approval_table) {
            return;
        }

        this.approval_data = extractApprovalTableData(
            this.getTranslatedApprovalStatesMap(),
            this.item.approval_table.approval_state,
            this.isInFolderContentRow
        );
    }
}
</script>
