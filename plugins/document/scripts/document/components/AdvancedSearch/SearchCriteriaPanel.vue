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
                <h1 class="tlp-pane-title" v-translate>Search criteria</h1>
            </div>
            <section class="tlp-pane-section search-criteria-panel-criteria-container">
                <search-criteria-breadcrumb v-if="!is_in_root_folder" />
                <div class="document-search-criteria">
                    <criterion-global-text v-model="new_query.global_search" />
                    <component
                        v-for="criterion in criteria"
                        v-bind:key="criterion.name"
                        v-bind:is="`criterion-${criterion.type}`"
                        v-bind:criterion="criterion"
                        v-model="new_query[criterion.name]"
                        v-bind:data-test="`criterion-${criterion.name}`"
                    />
                </div>
            </section>
            <section class="tlp-pane-section tlp-pane-section-submit search-criteria-panel-submit">
                <button
                    type="submit"
                    class="tlp-button-primary document-search-submit"
                    v-translate
                    data-test="submit"
                >
                    Apply
                </button>
            </section>
        </form>
    </section>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { namespace } from "vuex-class";
import type { AdvancedSearchParams, SearchCriteria } from "../../type";
import SearchCriteriaBreadcrumb from "./SearchCriteriaBreadcrumb.vue";
import CriterionGlobalText from "./Criteria/CriterionGlobalText.vue";
import { buildAdvancedSearchParams } from "../../helpers/build-advanced-search-params";
import CriterionText from "./Criteria/CriterionText.vue";
import CriterionOwner from "./Criteria/CriterionOwner.vue";
import CriterionDate from "./Criteria/CriterionDate.vue";
import CriterionList from "./Criteria/CriterionList.vue";
import CriterionNumber from "./Criteria/CriterionNumber.vue";

const configuration = namespace("configuration");

@Component({
    components: {
        CriterionDate,
        CriterionText,
        CriterionOwner,
        CriterionList,
        CriterionNumber,
        CriterionGlobalText,
        SearchCriteriaBreadcrumb,
    },
})
export default class SearchCriteriaPanel extends Vue {
    @Prop({ required: true })
    readonly query!: AdvancedSearchParams;

    @Prop({ required: true })
    readonly folder_id!: number;

    @configuration.State
    readonly root_id!: number;

    @configuration.State
    readonly criteria!: SearchCriteria;

    private new_query: AdvancedSearchParams = buildAdvancedSearchParams({});

    mounted() {
        this.new_query = this.query;
    }

    advancedSearch(): void {
        this.$emit("advanced-search", this.new_query);
    }

    get is_in_root_folder(): boolean {
        return this.folder_id === this.root_id;
    }
}
</script>
