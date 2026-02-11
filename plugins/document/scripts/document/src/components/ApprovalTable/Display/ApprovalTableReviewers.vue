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
    <table class="tlp-table document-approval-table">
        <thead>
            <tr>
                <th>{{ $gettext("Name") }}</th>
                <th>{{ $gettext("Review") }}</th>
                <th>{{ $gettext("Comment") }}</th>
                <th>{{ $gettext("Date") }}</th>
                <th>{{ $gettext("Version") }}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-if="reviewers.length === 0">
                <td colspan="5" class="tlp-table-cell-empty" data-test="no-reviewer">
                    {{ $gettext("There isn't any reviewers") }}
                </td>
            </tr>
            <tr
                v-else
                v-for="reviewer in reviewers"
                v-bind:key="reviewer.rank"
                v-bind:class="{
                    'tlp-table-row-danger': reviewer.state === 'rejected',
                    'tlp-table-row-success': reviewer.state === 'approved',
                }"
                data-test="reviewer-row"
            >
                <td>
                    <user-badge v-bind:user="reviewer.user" />
                </td>
                <td data-test="reviewer-state">
                    {{ translateReviewStatus(reviewer.state, $gettext) }}
                    <button
                        v-if="!is_readonly && reviewer.user.id === user_id"
                        ref="modal_trigger"
                        type="button"
                        class="tlp-button-primary tlp-button-mini review-button"
                        data-test="review-modal-trigger-button"
                    >
                        <i class="fa-solid fa-gavel tlp-button-icon" aria-hidden="true"></i>
                        {{ $gettext("Review") }}
                    </button>
                </td>
                <td>
                    <p v-dompurify-html="reviewer.post_processed_comment"></p>
                </td>
                <td>
                    <date-without-time
                        v-if="reviewer.review_date"
                        v-bind:date="reviewer.review_date"
                    />
                </td>
                <td>
                    <a
                        v-if="table.version_open_href !== null"
                        v-bind:href="table.version_open_href"
                    >
                        {{ reviewer.version_name }}
                    </a>
                    <template v-else>{{ reviewer.version_name }}</template>
                </td>
            </tr>
        </tbody>
    </table>

    <approval-table-review-modal
        v-if="!is_readonly && modal_trigger && current_reviewer"
        v-bind:item="item"
        v-bind:trigger="modal_trigger[0]"
        v-bind:reviewer="current_reviewer"
        v-bind:table="table"
    />
</template>

<script setup lang="ts">
import type { ApprovalTable, ApprovalTableReviewer, Item } from "../../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { USER_ID } from "../../../configuration-keys";
import UserBadge from "../../User/UserBadge.vue";
import ApprovalTableReviewModal from "../Review/ApprovalTableReviewModal.vue";
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { translateReviewStatus } from "../../../helpers/approval-table-helper";
import DateWithoutTime from "../../Date/DateWithoutTime.vue";

const { $gettext } = useGettext();

const props = defineProps<{
    item: Item;
    reviewers: ReadonlyArray<ApprovalTableReviewer>;
    is_readonly: boolean;
    table: ApprovalTable;
}>();

const user_id = strictInject(USER_ID);

const modal_trigger = ref<Array<HTMLButtonElement>>();

const current_reviewer = computed(() =>
    props.reviewers.find((reviewer) => reviewer.user.id === user_id),
);
</script>

<style scoped lang="scss">
.review-button {
    margin: 0 0 0 var(--tlp-small-spacing);
}

/* stylelint-disable selector-no-qualifying-type */
.tlp-table > tbody > tr.tlp-table-row-danger:not(.tlp-table-cell-actions),
.tlp-table > tbody > tr.tlp-table-row-danger > td:not(.tlp-table-cell-actions) {
    text-decoration: none;
}
/* stylelint-enable selector-no-qualifying-type */
</style>
