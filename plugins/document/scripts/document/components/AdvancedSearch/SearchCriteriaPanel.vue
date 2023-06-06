<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <section class="tlp-pane search-criteria-panel">
        <form class="tlp-pane-container" data-test="form" v-on:submit.prevent="advancedSearch">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">{{ $gettext("Search criteria") }}</h1>
            </div>
            <section class="tlp-pane-section search-criteria-panel-criteria-container">
                <search-criteria-breadcrumb v-if="!is_in_root_folder" />
                <div class="document-search-criteria" v-if="new_query">
                    <criterion-global-text
                        v-model="new_query.global_search"
                        v-bind:value="new_query.global_search"
                    />
                    <component
                        v-for="criterion in criteria"
                        v-bind:key="criterion.name"
                        v-bind:is="`criterion-${criterion.type}`"
                        v-bind:criterion="criterion"
                        v-model="new_query[criterion.name]"
                        v-bind:value="new_query[criterion.name]"
                    />
                </div>
            </section>
            <section class="tlp-pane-section tlp-pane-section-submit search-criteria-panel-submit">
                <button
                    type="submit"
                    class="tlp-button-primary document-search-submit"
                    data-test="submit"
                >
                    {{ $gettext("Apply") }}
                </button>
            </section>
        </form>
    </section>
</template>

<script setup lang="ts">
import type { AdvancedSearchParams } from "../../type";
import SearchCriteriaBreadcrumb from "./SearchCriteriaBreadcrumb.vue";
import CriterionGlobalText from "./Criteria/CriterionGlobalText.vue";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../store/configuration";
import type { Ref } from "vue";
// eslint-disable-next-line import/no-duplicates
import { computed, onMounted, ref } from "vue";

const props = defineProps<{ query: AdvancedSearchParams; folder_id: number }>();

const { root_id, criteria } = useNamespacedState<Pick<ConfigurationState, "root_id" | "criteria">>(
    "configuration",
    ["root_id", "criteria"]
);

const new_query: Ref<AdvancedSearchParams | null> = ref(null);

onMounted((): void => {
    new_query.value = props.query;
});

const emit = defineEmits<{
    (e: "advanced-search", value: AdvancedSearchParams | null): void;
}>();

function advancedSearch(): void {
    emit("advanced-search", new_query.value);
}

const is_in_root_folder = computed((): boolean => {
    return props.folder_id === root_id.value;
});
</script>

<script lang="ts">
// eslint-disable-next-line import/no-duplicates
import { defineComponent } from "vue";
import CriterionText from "./Criteria/CriterionText.vue";
import CriterionOwner from "./Criteria/CriterionOwner.vue";
import CriterionDate from "./Criteria/CriterionDate.vue";
import CriterionList from "./Criteria/CriterionList.vue";
import CriterionNumber from "./Criteria/CriterionNumber.vue";

export default defineComponent({
    components: {
        CriterionText,
        CriterionOwner,
        CriterionDate,
        CriterionList,
        CriterionNumber,
    },
});
</script>
