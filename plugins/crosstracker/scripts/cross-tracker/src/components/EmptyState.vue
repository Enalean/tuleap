<!--
  - Copyright (c) Enalean, 2024-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="empty-state-zone" data-test="cross-tracker-no-results">
        <empty-state-tumbleweed />
        <p class="empty-state-title" data-test="selectable-empty-state-title">
            {{
                writing_query.tql_query !== ""
                    ? $gettext("No artifact found")
                    : $gettext("Query is empty")
            }}
        </p>
        <p class="empty-state-text" data-test="selectable-empty-state-text">
            {{ getEmptyStateMessage() }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import EmptyStateTumbleweed from "./EmptyStateTumbleweed.vue";
import type { Query } from "../type";

const props = defineProps<{
    writing_query: Query;
}>();

const { $gettext } = useGettext();

function getEmptyStateMessage(): string {
    if (props.writing_query.tql_query === "") {
        return $gettext("Please create a new query.");
    }
    return $gettext("There is no artifact matching the query.");
}
</script>

<style scoped lang="scss">
.empty-state-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
}
</style>
