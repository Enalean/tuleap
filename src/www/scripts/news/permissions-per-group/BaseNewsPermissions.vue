/**
* Copyright (c) Enalean, 2018. All Rights Reserved.
*
* This file is a part of Tuleap.
*
* Tuleap is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Tuleap is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

(<template>
    <section class="tlp-pane-section">
        <div v-if="hasRestError"
             class="tlp-alert-danger"
        >
            {{ rest_error }}
        </div>

        <div class="permission-per-group-load-button" v-if="! is_loaded">
            <button class="tlp-button-primary tlp-button-outline"
                    v-on:click="loadAll"
            >
                {{ load_all_label }}
            </button>
        </div>

        <div v-if="is_loading"
             v-bind:aria-label="news_are_loading"
             class="permission-per-group-loader"
        ></div>

        <table v-if="is_loaded" class="tlp-table">
            <thead>
                <tr class="permission-per-group-double-column-table">
                    <th>{{ news_label }}</th>
                    <th>{{ visibility_label }}</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="news in news_list" v-bind:key="news.news_name">
                    <td>
                        <a v-bind:href="news.admin_quicklink">{{ news.news_name }}</a>
                    </td>

                    <visibility-label v-bind:is-visibility-public="news.is_public"></visibility-label>
                </tr>

                <tr v-if="! hasNewsToDisplay">
                    <td v-if="hasASelectedUserGroup"
                        colspan="2"
                        class="tlp-table-cell-empty"
                    >
                        {{ user_group_no_news_empty_state }}
                    </td>

                    <td v-else
                        colspan="2"
                        class="tlp-table-cell-empty"
                    >
                        {{ project_no_news_empty_state }}
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>)

(<script>
import { sprintf } from "sprintf-js";
import { gettext_provider } from "./gettext-provider.js";
import { getNewsPermissions } from "./rest-querier.js";
import VisibilityLabel from "./NewsPermissionsVisibilityLabel.vue";

export default {
    name: "NewsPermissions",
    props: {
        selectedUgroupId: String,
        selectedProjectId: String,
        selectedUgroupName: String
    },
    components: {
        VisibilityLabel
    },
    data() {
        return {
            is_loaded: false,
            is_loading: false,
            rest_error: null,
            news_list: []
        };
    },
    computed: {
        hasNewsToDisplay() {
            return this.news_list.length > 0;
        },
        hasASelectedUserGroup() {
            return this.selectedUgroupId.length > 0;
        },
        user_group_no_news_empty_state() {
            return sprintf(
                gettext_provider.gettext("%s can't see any news"),
                this.selectedUgroupName
            );
        },
        hasRestError() {
            return this.rest_error !== null;
        },
        project_no_news_empty_state: () => gettext_provider.gettext("The project hasn't any news"),
        news_label: () => gettext_provider.gettext("News"),
        visibility_label: () => gettext_provider.gettext("Visibility"),
        load_all_label: () => gettext_provider.gettext("See all news"),
        news_are_loading: () => gettext_provider.gettext("News are loading")
    },
    methods: {
        async loadAll() {
            try {
                this.is_loading = true;

                this.news_list = await getNewsPermissions(
                    this.selectedProjectId,
                    this.selectedUgroupId
                );

                this.is_loaded = true;
            } catch (e) {
                const { error } = await e.response.json();
                this.rest_error = error.code + " " + error.message;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>)
