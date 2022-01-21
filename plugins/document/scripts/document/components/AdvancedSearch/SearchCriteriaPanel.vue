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
    <section class="tlp-pane">
        <form class="tlp-pane-container" v-on:submit.prevent="advancedSearch">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title" v-translate>Search criteria</h1>
            </div>
            <section class="tlp-pane-section">
                <div class="tlp-form-element">
                    <label class="tlp-label" for="document-global-search" v-translate>
                        Global search
                    </label>
                    <input
                        type="text"
                        class="tlp-input"
                        id="document-global-search"
                        v-model="query"
                    />
                </div>
            </section>
            <section class="tlp-pane-section tlp-pane-section-submit">
                <button type="submit" class="tlp-button-primary" v-translate>Apply</button>
            </section>
        </form>
    </section>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";

@Component
export default class SearchCriteriaPanel extends Vue {
    @Prop({ required: true })
    readonly query!: string;

    advancedSearch(): void {
        this.$router.push({
            name: "search",
            query: {
                q: this.query,
            },
        });
    }
}
</script>
