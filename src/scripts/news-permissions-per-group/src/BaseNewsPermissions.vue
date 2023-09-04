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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <section class="tlp-pane-section">
        <div v-if="hasRestError" class="tlp-alert-danger">
            {{ rest_error }}
        </div>

        <div class="permission-per-group-load-button" v-if="!is_loaded">
            <button
                class="tlp-button-primary tlp-button-outline"
                v-on:click="loadAll()"
                v-translate
            >
                See all news
            </button>
        </div>

        <div
            v-if="is_loading"
            v-bind:aria-label="news_are_loading"
            class="permission-per-group-loader"
        ></div>

        <table v-if="is_loaded" class="tlp-table">
            <thead>
                <tr class="permission-per-group-double-column-table">
                    <th v-translate>News</th>
                    <th v-translate>Visibility</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="news in news_list" v-bind:key="news.news_name">
                    <td>
                        <a v-bind:href="news.admin_quicklink">{{ news.news_name }}</a>
                    </td>

                    <visibility-label v-bind:is-visibility-public="news.is_public" />
                </tr>

                <tr v-if="!hasNewsToDisplay">
                    <td
                        v-if="hasASelectedUserGroup"
                        key="selected-ugroup"
                        colspan="2"
                        class="tlp-table-cell-empty"
                        v-translate="{ user_group: selectedUgroupName }"
                    >
                        %{ user_group } can't see any news
                    </td>

                    <td
                        v-else
                        key="no-selected-ugroup"
                        colspan="2"
                        class="tlp-table-cell-empty"
                        v-translate
                    >
                        The project hasn't any news
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script>
import { getNewsPermissions } from "./rest-querier.js";
import VisibilityLabel from "./NewsPermissionsVisibilityLabel.vue";

export default {
    name: "BaseNewsPermissions",
    components: {
        VisibilityLabel,
    },
    props: {
        selectedUgroupId: String,
        selectedProjectId: String,
        selectedUgroupName: String,
    },
    data() {
        return {
            is_loaded: false,
            is_loading: false,
            rest_error: null,
            news_list: [],
        };
    },
    computed: {
        hasNewsToDisplay() {
            return this.news_list.length > 0;
        },
        hasASelectedUserGroup() {
            return this.selectedUgroupId.length > 0;
        },
        hasRestError() {
            return this.rest_error !== null;
        },
        news_are_loading() {
            return this.$gettext("News are loading");
        },
    },
    methods: {
        async loadAll() {
            try {
                this.is_loading = true;

                this.news_list = await getNewsPermissions(
                    this.selectedProjectId,
                    this.selectedUgroupId,
                );

                this.is_loaded = true;
            } catch (e) {
                const { error } = await e.response.json();
                this.rest_error = error.code + " " + error.message;
            } finally {
                this.is_loading = false;
            }
        },
    },
};
</script>
