<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <div>
        <gitlab-group-link-wizard v-bind:active_step_id="STEP_GITLAB_GROUP" />

        <div class="tlp-framed-vertically">
            <h2>{{ $gettext("GitLab group selection") }}</h2>
            <section class="tlp-pane">
                <form class="tlp-pane-container">
                    <section class="tlp-pane-section">
                        <table class="tlp-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th
                                        colspan="2"
                                        class="gitlab-select-group-table-group-column-header"
                                    >
                                        {{ $gettext("Group") }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="group in groups_store.groups"
                                    v-bind:key="group.id"
                                    data-test="gitlab-group-row"
                                >
                                    <td class="gitlab-select-group-radio-button-container">
                                        <input
                                            type="radio"
                                            v-bind:id="'gitlab_group_' + group.id"
                                            v-bind:value="group"
                                            v-model="selected_group"
                                            data-test="gitlab-select-group-radio-button"
                                        />
                                    </td>
                                    <td class="gitlab-group-avatar-container">
                                        <img
                                            v-if="group.avatar_url !== null"
                                            v-bind:src="group.avatar_url"
                                            v-bind:alt="group.full_path"
                                            class="gitlab-group-avatar"
                                            data-test="gitlab-group-avatar"
                                        />
                                        <div
                                            v-else
                                            class="default-gitlab-group-avatar gitlab-group-avatar"
                                            data-test="gitlab-group-avatar"
                                        >
                                            {{ group.name[0] }}
                                        </div>
                                    </td>
                                    <td class="gitlab-group">
                                        <label
                                            v-bind:for="'gitlab_group_' + group.id"
                                            class="gitlab-group-name"
                                            data-test="gitlab-group-name"
                                            >{{ group.name }}
                                            <span class="gitlab-group-full-path">
                                                ({{ group.full_path }})
                                            </span>
                                        </label>
                                    </td>
                                </tr>
                                <tr v-if="!groups_store.groups.length">
                                    <td
                                        colspan="3"
                                        class="tlp-table-cell-empty"
                                        data-test="gitlab-group-empty-state"
                                    >
                                        {{
                                            $gettext(
                                                "There isn't any group, please verify the permissions on GitLab's side",
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="gitlab-select-group-step-action-buttons">
                            <router-link
                                v-bind:to="{ name: STEP_GITLAB_SERVER }"
                                class="tlp-button-primary tlp-button-outline"
                            >
                                <i class="fas fa-arrow-left tlp-button-icon" aria-hidden="true"></i>
                                {{ $gettext("Back") }}
                            </router-link>
                            <button
                                type="submit"
                                class="tlp-button-primary gitlab-select-group-step-button-submit"
                                v-bind:disabled="has_no_selected_group"
                                v-on:click="onClickConfigureSelectedGroup"
                                data-test="gitlab-select-group-submit-button"
                            >
                                {{ $gettext("Configure selected group") }}
                                <i
                                    class="fas fa-arrow-right tlp-button-icon tlp-button-icon-right"
                                    aria-hidden="true"
                                ></i>
                            </button>
                        </div>
                    </section>
                </form>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import type { Ref } from "vue";
import { useRouter } from "vue-router";

import { STEP_GITLAB_GROUP, STEP_GITLAB_SERVER, STEP_GITLAB_CONFIGURATION } from "../types";
import GitlabGroupLinkWizard from "./GitlabGroupLinkWizard.vue";

import { useGitLabGroupsStore } from "../stores/groups";
import type { GitlabGroup } from "../stores/types";

const groups_store = useGitLabGroupsStore();
const selected_group: Ref<GitlabGroup | null> = ref(groups_store.selected_group);
const router = useRouter();

const has_no_selected_group = computed(() => selected_group.value === null);

function onClickConfigureSelectedGroup(): void {
    if (selected_group.value === null) {
        return;
    }

    groups_store.setSelectedGroup(selected_group.value);
    router.push({ name: STEP_GITLAB_CONFIGURATION });
}
</script>

<style scoped lang="scss">
.gitlab-select-group-step-action-buttons {
    display: flex;
    margin: var(--tlp-large-spacing) 0 0;
}

.gitlab-select-group-step-button-submit {
    margin: 0 0 0 var(--tlp-medium-spacing);
}

.gitlab-select-group-radio-button-container {
    width: 0;
}

/* stylelint-disable-next-line selector-no-qualifying-type */
.tlp-table > tbody > tr > td.gitlab-group-avatar-container {
    width: 1.25rem;
    padding: 0;
    font-size: 0.65rem;
    font-weight: 600;
    line-height: 1.25rem;
    vertical-align: middle;
}

.gitlab-group-avatar {
    display: block;
    width: 100%;
    height: 100%;
    border-radius: var(--tlp-small-radius);
}

.default-gitlab-group-avatar {
    background-color: var(--tlp-background-color);
    color: var(--tlp-dimmed-color);
    text-align: center;
    text-transform: capitalize;
}

.gitlab-group-full-path {
    margin: 0 0 0 2px;
    color: var(--tlp-dimmed-color);
    font-size: 0.75rem;
}

.gitlab-select-group-table-group-column-header {
    padding-left: 0;
}
</style>
