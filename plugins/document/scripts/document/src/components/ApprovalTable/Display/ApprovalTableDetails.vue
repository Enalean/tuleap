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
    <div class="tlp-property">
        <label class="tlp-label">{{ $gettext("Approval requester") }}</label>
        <user-badge v-bind:user="table.table_owner" />
    </div>
    <div class="tlp-property" v-if="table.version_number !== null">
        <label class="tlp-label">{{ $gettext("Attached to document version") }}</label>
        <p data-test="table-version-number">{{ table.version_number }}</p>
    </div>
    <div class="tlp-property">
        <label class="tlp-label">{{ $gettext("Notification type") }}</label>
        <p data-test="table-notification">
            {{ translateNotificationType(table.notification_type, $gettext) }}
        </p>
    </div>
    <div class="tlp-property">
        <label class="tlp-label">{{ $gettext("Approval cycle start date") }}</label>
        <document-relative-date
            v-bind:date="table.approval_request_date"
            relative_placement="right"
        />
    </div>
    <div class="tlp-property" v-if="table.is_closed">
        <label class="tlp-label">{{ $gettext("Table status") }}</label>
        <p data-test="table-closed">{{ $gettext("Closed") }}</p>
    </div>
    <div class="tlp-property">
        <label class="tlp-label">{{ $gettext("Requester comment") }}</label>
        <p v-if="table.description !== ''" data-test="table-description">{{ table.description }}</p>
        <p v-else class="tlp-property-empty">{{ $gettext("No comment") }}</p>
    </div>

    <approval-table-reviewers
        v-bind:reviewers="table.reviewers"
        v-bind:item="item"
        v-bind:is_readonly="is_readonly"
        v-bind:table="table"
        v-on:refresh-data="$emit('refresh-data')"
    />
</template>

<script setup lang="ts">
import type { ApprovalTable, Item } from "../../../type";
import UserBadge from "../../User/UserBadge.vue";
import DocumentRelativeDate from "../../Date/DocumentRelativeDate.vue";
import ApprovalTableReviewers from "./ApprovalTableReviewers.vue";
import { translateNotificationType } from "../../../helpers/approval-table-helper";

defineProps<{
    item: Item;
    table: ApprovalTable;
    is_readonly: boolean;
}>();

defineEmits<{ (e: "refresh-data"): void }>();
</script>
