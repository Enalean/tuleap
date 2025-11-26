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
                    'reviewer-not-current': is_readonly || reviewer.user.id !== user_id,
                }"
                data-test="reviewer-row"
            >
                <td>
                    <user-badge v-bind:user="reviewer.user" />
                </td>
                <td data-test="reviewer-state">
                    <template v-if="is_readonly || reviewer.user.id !== user_id">
                        {{ reviewer.state }}
                    </template>
                    <a v-else v-bind:href="getLinkToReview()">{{ reviewer.state }}</a>
                </td>
                <td>{{ reviewer.comment }}</td>
                <td>
                    <document-relative-date
                        v-if="reviewer.review_date"
                        v-bind:date="reviewer.review_date"
                        relative_placement="right"
                    />
                </td>
                <td>
                    <router-link
                        v-if="reviewer.version_id !== null && reviewer.version_name !== null"
                        v-bind:to="{
                            name: 'item_version',
                            params: {
                                folder_id: item.parent_id,
                                item_id: item.id,
                                version_id: reviewer.version_id,
                            },
                        }"
                    >
                        {{ reviewer.version_name }}
                    </router-link>
                    <template v-else>{{ reviewer.version_name }}</template>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup lang="ts">
import type { ApprovalTableReviewer, Item } from "../../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT, USER_ID } from "../../../configuration-keys";
import UserBadge from "../../User/UserBadge.vue";
import DocumentRelativeDate from "../../Date/DocumentRelativeDate.vue";

const props = defineProps<{
    item: Item;
    reviewers: ReadonlyArray<ApprovalTableReviewer>;
    is_readonly: boolean;
}>();

const user_id = strictInject(USER_ID);
const project = strictInject(PROJECT);

function getLinkToReview(): string {
    return `/plugins/docman/?group_id=${project.id}&action=details&id=${props.item.id}&section=approval&review=1`;
}
</script>

<style scoped lang="scss">
.reviewer-not-current {
    font-style: italic;
}
</style>
