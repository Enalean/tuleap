<!--
  - Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
    <div
        class="query"
        v-bind:class="{ disabled: !is_user_admin }"
        v-on:click="switchToWritingMode"
        data-test="cross-tracker-reading-mode"
    >
        <label
            v-if="props.reading_query.description !== ''"
            class="tlp-label"
            v-bind:for="syntax_highlighted_query_id"
            data-test="query-description"
        >
            {{ props.reading_query.description }}
        </label>
        <tlp-syntax-highlighting
            v-bind:id="syntax_highlighted_query_id"
            data-test="tql-reading-mode-query"
        >
            <code class="language-tql cross-tracker-reading-mode-query">{{
                props.reading_query.tql_query
            }}</code>
        </tlp-syntax-highlighting>
    </div>
    <reading-mode-action-buttons v-bind:current_query="reading_query" />
</template>
<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Query } from "../../type";
import { IS_USER_ADMIN, WIDGET_ID } from "../../injection-symbols";
import ReadingModeActionButtons from "./ReadingModeActionButtons.vue";

const widget_id = strictInject(WIDGET_ID);
const is_user_admin = strictInject(IS_USER_ADMIN);

const props = defineProps<{
    has_error: boolean;
    reading_query: Query;
}>();

const emit = defineEmits<{
    (e: "switch-to-writing-mode"): void;
}>();

const syntax_highlighted_query_id = "syntax-highlighted-query-" + widget_id;

function switchToWritingMode(): void {
    if (!is_user_admin) {
        return;
    }
    emit("switch-to-writing-mode");
}
</script>

<style scoped lang="scss">
.query {
    display: flex;
    flex-direction: column;
    margin: var(--tlp-medium-spacing) 0;
    padding: var(--tlp-small-spacing);
    border-radius: var(--tlp-small-radius);
    color: var(--tlp-main-color);
    font-size: 0.9375rem;
    gap: var(--tlp-medium-spacing);

    &:not(.disabled) {
        cursor: pointer;
    }

    &:hover:not(.disabled) {
        background-color: var(--tlp-main-color-transparent-80);
    }
}

.cross-tracker-reading-mode-query {
    padding: 3px 0;
    background: transparent;
}
</style>
