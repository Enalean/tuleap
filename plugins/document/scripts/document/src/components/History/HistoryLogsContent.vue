<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <tbody>
        <tr v-for="(entry, index) in log_entries" v-bind:key="'log-' + index">
            <td>
                <document-relative-date v-bind:date="entry.when" />
            </td>
            <td>
                <user-badge v-bind:user="entry.who" />
            </td>
            <td>{{ entry.what }}</td>
            <template v-if="entry.old_value !== null">
                <td
                    v-bind:colspan="entry.new_value === null ? 2 : 1"
                    v-bind:class="{
                        'document-history-merged-values': entry.new_value === null,
                    }"
                >
                    {{ entry.old_value }}
                </td>
            </template>
            <template v-if="entry.new_value !== null">
                <td
                    v-bind:colspan="entry.old_value === null ? 2 : 1"
                    v-bind:class="{
                        'document-history-merged-values': entry.old_value === null,
                    }"
                >
                    {{ entry.new_value }}
                </td>
            </template>
            <template v-if="entry.old_value === null && entry.new_value === null">
                <td colspan="2" class="document-history-merged-values">
                    <a v-if="entry.diff_link" v-bind:href="entry.diff_link">{{
                        $gettext("diff")
                    }}</a>
                </td>
            </template>
        </tr>
    </tbody>
</template>

<script setup lang="ts">
import UserBadge from "../User/UserBadge.vue";
import type { LogEntry } from "../../api/log-rest-querier";
import DocumentRelativeDate from "../Date/DocumentRelativeDate.vue";

defineProps<{ log_entries: readonly LogEntry[] }>();
</script>
