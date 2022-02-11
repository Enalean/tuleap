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
    <div class="tlp-framed-horizontally">
        <section class="tlp-pane">
            <form class="tlp-pane-container" data-test="form" v-on:submit.prevent="advancedSearch">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title" v-translate>Search criteria</h1>
                </div>
                <section class="tlp-pane-section">
                    <search-criteria-breadcrumb v-if="!is_in_root_folder" />
                    <div class="document-search-criteria">
                        <criterion-global-text v-model="new_query.global_search" />
                        <criterion-type v-model="new_query.type" />
                        <criterion-text
                            name="title"
                            v-bind:label="$gettext('Title')"
                            v-model="new_query.title"
                            data-test="criterion-title"
                        />
                        <criterion-text
                            name="description"
                            v-bind:label="$gettext('Description')"
                            v-model="new_query.description"
                            data-test="criterion-description"
                        />
                        <criterion-text
                            name="owner"
                            v-bind:label="$gettext('Owner')"
                            v-model="new_query.owner"
                            data-test="criterion-owner"
                        />
                    </div>
                </section>
                <section class="tlp-pane-section tlp-pane-section-submit">
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
    </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { namespace } from "vuex-class";
import type { AdvancedSearchParams } from "../../type";
import SearchCriteriaBreadcrumb from "./SearchCriteriaBreadcrumb.vue";
import CriterionGlobalText from "./Criteria/CriterionGlobalText.vue";
import CriterionType from "./Criteria/CriterionType.vue";
import CriterionText from "./Criteria/CriterionText.vue";
import { buildAdvancedSearchParams } from "../../helpers/build-advanced-search-params";

const configuration = namespace("configuration");

@Component({
    components: {
        CriterionText,
        CriterionType,
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
