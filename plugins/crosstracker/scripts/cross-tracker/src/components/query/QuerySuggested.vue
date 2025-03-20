<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
    <div class="query-suggestion tlp-label query-suggestion-label tlp-form-element">
        {{ $gettext("Query suggestions") }}
    </div>
    <div class="tlp-form-element">
        <div class="query-suggestion-buttons">
            <button
                class="tlp-button-primary tlp-button-outline tlp-button"
                v-for="suggested_query in getTranslatedQueries()"
                v-bind:key="suggested_query.title"
                v-on:click="handleButtonClick(suggested_query)"
                data-test="query-suggested-button"
            >
                {{ suggested_query.title }}
            </button>
        </div>
        <p class="tlp-text-muted">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            {{
                $gettext(
                    "If you click on one of the suggested query, your content will be replaced.",
                )
            }}
        </p>
        <query-suggested-modal v-on:query-chosen="(query) => emit('query-chosen', query)" />
    </div>
</template>
<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { DASHBOARD_TYPE, EMITTER, GET_SUGGESTED_QUERIES } from "../../injection-symbols";
import type { QuerySuggestion } from "../../domain/SuggestedQueriesGetter";
import { PROJECT_DASHBOARD } from "../../domain/DashboardType";
import QuerySuggestedModal from "./QuerySuggestedModal.vue";
import { DISPLAY_QUERY_PREVIEW_EVENT } from "../../helpers/emitter-provider";

const props = defineProps<{
    is_modal_should_be_displayed: boolean;
}>();

const emit = defineEmits<{
    (e: "query-chosen", query: QuerySuggestion): void;
}>();

const dashboard_type = strictInject(DASHBOARD_TYPE);
const suggested_query_getter = strictInject(GET_SUGGESTED_QUERIES);
const emitter = strictInject(EMITTER);

function handleButtonClick(query: QuerySuggestion): void {
    if (props.is_modal_should_be_displayed) {
        emitter.emit(DISPLAY_QUERY_PREVIEW_EVENT, { query });
        return;
    }
    emit("query-chosen", query);
}

function getTranslatedQueries(): QuerySuggestion[] {
    if (dashboard_type === PROJECT_DASHBOARD) {
        return suggested_query_getter.getTranslatedProjectSuggestedQueries();
    }
    return suggested_query_getter.getTranslatedPersonalSuggestedQueries();
}
</script>
<style scoped lang="scss">
.query-suggestion-buttons {
    display: flex;
    gap: var(--tlp-small-spacing);
}
</style>
