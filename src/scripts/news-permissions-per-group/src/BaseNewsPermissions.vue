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
        <div v-if="has_rest_error" class="tlp-alert-danger">
            {{ rest_error }}
        </div>

        <div class="permission-per-group-load-button" v-if="!is_loaded">
            <button class="tlp-button-primary tlp-button-outline" v-on:click="loadAll()">
                {{ $gettext("See all news") }}
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
                    <th>{{ $gettext("News") }}</th>
                    <th>{{ $gettext("Visibility") }}</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="news in news_list" v-bind:key="news.news_name">
                    <td>
                        <a v-bind:href="news.admin_quicklink">{{ news.news_name }}</a>
                    </td>

                    <visibility-label v-bind:is_visibility_public="news.is_public" />
                </tr>

                <tr v-if="news_list.length === 0">
                    <td
                        v-if="selected_ugroup_name.length > 0"
                        key="selected-ugroup"
                        colspan="2"
                        class="tlp-table-cell-empty"
                    >
                        {{
                            interpolate($gettext("%{ user_group } can't see any news"), {
                                user_group: selected_ugroup_name,
                            })
                        }}
                    </td>

                    <td v-else key="no-selected-ugroup" colspan="2" class="tlp-table-cell-empty">
                        {{ $gettext("The project hasn't any news") }}
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup lang="ts">
import type { NewsVisibilityRepresentation } from "./rest-querier";
import { getNewsPermissions } from "./rest-querier";
import VisibilityLabel from "./NewsPermissionsVisibilityLabel.vue";
import type { Fault } from "@tuleap/fault";
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    selected_project_id: number;
    selected_ugroup_id: string;
    selected_ugroup_name: string;
}>();

const rest_error = ref<string | null>(null);
const is_loaded = ref(false);
const is_loading = ref(false);
const has_rest_error = computed(() => rest_error.value !== null);
const news_list = ref<NewsVisibilityRepresentation[]>([]);

const { interpolate, $gettext } = useGettext();

const news_are_loading = ref($gettext("News are loading"));

function loadAll(): void {
    is_loading.value = true;
    getNewsPermissions(props.selected_project_id, props.selected_ugroup_id).match(
        (permissions: NewsVisibilityRepresentation[]) => {
            is_loaded.value = true;
            is_loading.value = false;
            news_list.value = permissions;
        },
        (fault: Fault) => {
            is_loading.value = false;
            rest_error.value = String(fault);
        },
    );
}
</script>
