<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -
  -->

<template>
    <div class="tlp-form-element document-header-filter-container">
        <input
            type="search"
            class="tlp-search tlp-search-small document-search-box"
            v-bind:placeholder="`${$gettext('Name, description...')}`"
            v-model="search_query"
            data-shortcut-search-document
            v-on:keyup.enter="advancedSearch()"
            v-if="search_for_document_with_criteria"
        />
        <router-link
            v-bind:to="{ name: 'search' }"
            v-bind:title="`${$gettext('Search')}`"
            v-if="search_for_document_with_criteria"
        >
            <a class="document-advanced-link" data-test="document-advanced-link" v-translate>
                Advanced
            </a>
        </router-link>
        <div v-else>
            <input
                type="search"
                class="tlp-search tlp-search-small document-search-box"
                v-bind:placeholder="`${$gettext('Name, description...')}`"
                v-model="search_query"
                v-on:keyup.enter="searchUrl()"
                data-shortcut-search-document
            />
            <a
                class="document-advanced-link"
                v-bind:href="advancedUrl()"
                data-test="document-advanced-link"
                v-translate
            >
                Advanced
            </a>
        </div>
    </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { namespace, State } from "vuex-class";
import type { Folder } from "../../type";
// We need to manually import because router is still in JS
// eslint-disable-next-line @typescript-eslint/no-unused-vars
import type Router from "vue-router";

const configuration = namespace("configuration");

@Component
export default class SearchBox extends Vue {
    @State
    readonly current_folder!: Folder;
    @configuration.State
    readonly project_id!: number;
    @configuration.State
    readonly search_for_document_with_criteria!: boolean;

    private search_query = "";

    advancedUrl(): string {
        return (
            "/plugins/docman/?group_id=" +
            encodeURIComponent(this.project_id) +
            "&id=" +
            encodeURIComponent(this.current_folder.id) +
            "&action=search&global_txt=" +
            encodeURIComponent(this.search_query) +
            "&sort_update_date=0&add_filter=--&save_report=--&filtersubmit=Apply"
        );
    }

    advancedSearch(): void {
        this.$router.push({
            name: "search",
            query: {
                q: this.search_query,
            },
        });
    }

    searchUrl(): void {
        const encoded_url =
            "/plugins/docman/?group_id=" +
            encodeURIComponent(this.project_id) +
            "&id=" +
            encodeURIComponent(this.current_folder.id) +
            "&action=search&global_txt=" +
            encodeURIComponent(this.search_query) +
            "&global_filtersubmit=Apply";
        window.location.assign(encoded_url);
    }
}
</script>
