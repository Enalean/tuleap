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
                    <div class="tlp-form-element">
                        <div class="global-search-label">
                            <label class="tlp-label" for="document-global-search" v-translate>
                                Global search
                            </label>
                            <search-information-popover />
                        </div>

                        <input
                            type="text"
                            class="tlp-input"
                            id="document-global-search"
                            v-model="new_query"
                            data-test="global-search"
                        />
                    </div>
                </section>
                <section class="tlp-pane-section tlp-pane-section-submit">
                    <button type="submit" class="tlp-button-primary" v-translate data-test="submit">
                        Apply
                    </button>
                </section>
            </form>
        </section>
    </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import type { AdvancedSearchParams } from "../../type";
import SearchInformationPopover from "./SearchInformationPopover.vue";
@Component({
    components: { SearchInformationPopover },
})
export default class SearchCriteriaPanel extends Vue {
    @Prop({ required: true })
    readonly query!: string;

    new_query = "";

    mounted() {
        this.new_query = this.query;
    }

    advancedSearch(): void {
        const params: AdvancedSearchParams = {
            query: this.new_query,
        };

        this.$emit("advanced-search", params);
    }
}
</script>
