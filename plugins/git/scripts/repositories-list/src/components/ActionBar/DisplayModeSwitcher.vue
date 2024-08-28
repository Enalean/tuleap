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
                data-test="git-repository-list-switch-last-update"
                class="tlp-button-primary tlp-button-outline"
                v-bind:class="{ disabled: isLoading }"
                v-bind:title="$gettext('Sort repositories by their last update date')"
            >
                <span class="fa-stack">
                    <i class="fas fa-long-arrow-alt-down"></i>
                    <i class="fas fa-calendar-alt"></i>
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
                v-bind:title="$gettext('Sort repositories alphabetically')"
                data-test="git-repository-list-switch-path"
            >
                <i class="fas fa-fw fa-sort-alpha-down"></i>
            </label>
        </div>
    </div>
</template>
<script setup lang="ts">
import {
    REPOSITORIES_SORTED_BY_LAST_UPDATE as repositories_sorted_by_last_update,
    REPOSITORIES_SORTED_BY_PATH as repositories_sorted_by_path,
} from "../../constants";
import { onMounted, ref, watch } from "vue";
import { useActions, useGetters, useState } from "vuex-composition-helpers";

const { isLoading } = useGetters(["isLoading"]);
const { setDisplayMode } = useActions(["setDisplayMode"]);
const { display_mode } = useState(["display_mode"]);
const current_display_mode = ref<string | null>(null);

onMounted((): void => {
    current_display_mode.value =
        display_mode.value !== "" ? display_mode.value : repositories_sorted_by_last_update;
});

watch(current_display_mode, (value: string | null) => {
    setDisplayMode(value);
});
</script>
