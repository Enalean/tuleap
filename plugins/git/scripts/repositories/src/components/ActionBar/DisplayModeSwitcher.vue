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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->
<template>
    <div class="tlp-button-bar git-repository-list-actions-display-switch">
        <div class="tlp-button-bar-item">
            <input
                type="radio"
                name="display-mode-switch"
                id="git-repository-list-switch-last-update"
                class="tlp-button-bar-checkbox"
                v-bind:value="repositories_sorted_by_last_update"
                v-model="current_display_mode"
                v-bind:disabled="isLoading"
            />
            <label
                for="git-repository-list-switch-last-update"
                class="tlp-button-primary tlp-button-outline"
                v-bind:class="{ disabled: isLoading }"
                v-bind:title="sort_by_last_update_title"
            >
                <span class="fa-stack">
                    <i class="fa fa-long-arrow-down"></i>
                    <i class="fa fa-calendar"></i>
                </span>
            </label>
        </div>
        <div class="tlp-button-bar-item">
            <input
                type="radio"
                name="display-mode-switch"
                id="git-repository-list-switch-path"
                class="tlp-button-bar-checkbox"
                v-bind:value="repositories_sorted_by_path"
                v-model="current_display_mode"
                v-bind:disabled="isLoading"
            />
            <label
                for="git-repository-list-switch-path"
                class="tlp-button-primary tlp-button-outline git-repository-list-switch-path-label"
                v-bind:class="{ disabled: isLoading }"
                v-bind:title="sort_by_path_title"
            >
                <i class="fa fa-fw fa-sort-alpha-asc"></i>
            </label>
        </div>
    </div>
</template>
<script>
import { mapGetters } from "vuex";
import {
    REPOSITORIES_SORTED_BY_LAST_UPDATE,
    REPOSITORIES_SORTED_BY_PATH,
} from "../../constants.js";

export default {
    name: "DisplayModeSwitcher",
    computed: {
        sort_by_last_update_title() {
            return this.$gettext("Sort repositories by their last update date");
        },
        sort_by_path_title() {
            return this.$gettext("Sort repositories alphabetically");
        },
        repositories_sorted_by_last_update() {
            return REPOSITORIES_SORTED_BY_LAST_UPDATE;
        },
        repositories_sorted_by_path() {
            return REPOSITORIES_SORTED_BY_PATH;
        },
        current_display_mode: {
            get() {
                return this.$store.state.display_mode;
            },
            set(value) {
                return this.$store.dispatch("setDisplayMode", value);
            },
        },
        ...mapGetters(["isLoading"]),
    },
};
</script>
