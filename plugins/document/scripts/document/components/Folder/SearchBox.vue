<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
            class="tlp-search document-search-box"
            v-bind:placeholder="placeholder_text"
            v-model="search_query"
            v-on:keyup.enter="searchUrl"
        />
        <a v-bind:title="advanced_title" class="document-advanced-link" v-bind:href="advanced_url">
            {{ advanced_title }}
        </a>
    </div>
</template>

<script>
import { mapState } from "vuex";

export default {
    name: "SearchBox",
    data() {
        return {
            search_query: "",
        };
    },
    computed: {
        ...mapState(["project_id", "current_folder"]),
        placeholder_text() {
            return this.$gettext("Name, description...");
        },
        advanced_url() {
            return (
                "/plugins/docman/?group_id=" +
                encodeURIComponent(this.project_id) +
                "&id=" +
                encodeURIComponent(this.current_folder.id) +
                "&action=search&global_txt=" +
                encodeURIComponent(this.search_query) +
                "&sort_update_date=0&add_filter=--&save_report=--&filtersubmit=Apply"
            );
        },
        advanced_title() {
            return this.$gettext("Advanced");
        },
    },
    methods: {
        searchUrl() {
            const encoded_url =
                "/plugins/docman/?group_id=" +
                encodeURIComponent(this.project_id) +
                "&id=" +
                encodeURIComponent(this.current_folder.id) +
                "&action=search&global_txt=" +
                encodeURIComponent(this.search_query) +
                "&global_filtersubmit=Apply";
            window.location.assign(encoded_url);
        },
    },
};
</script>
