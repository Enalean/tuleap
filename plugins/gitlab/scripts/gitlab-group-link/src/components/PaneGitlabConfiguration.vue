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
        <gitlab-group-link-wizard v-bind:active_step_id="STEP_GITLAB_CONFIGURATION" />

        <div class="tlp-framed-vertically">
            <h1>{{ $gettext("Associated group configuration") }}</h1>
            <section class="tlp-pane">
                <form class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        {{
                            $gettext(
                                "Please note that the following settings will be applied to all linked repositories and may be changed by each repository afterwards."
                            )
                        }}
                    </div>
                    <section class="tlp-pane-section">
                        <div class="tlp-form-element">
                            <label class="tlp-label tlp-checkbox">
                                <input type="checkbox" v-model="is_artifact_closure_allowed" />
                                {{ $gettext("Allow artifact closure") }}
                            </label>
                            <p class="tlp-text-info">
                                <i class="far fa-life-ring" aria-hidden="true"></i>
                                {{
                                    $gettext(
                                        "If selected, artifacts of this project can be closed with GitLab commit messages from the selected repository."
                                    )
                                }}
                            </p>
                        </div>
                        <div class="tlp-form-element">
                            <label class="tlp-label tlp-checkbox">
                                <input
                                    type="checkbox"
                                    v-model="uses_branch_name_prefix"
                                    data-test="checkbox-prefix-branch-name"
                                />
                                {{ $gettext("Prefix the branch name") }}
                            </label>
                            <p class="tlp-text-info">
                                <i class="far fa-life-ring" aria-hidden="true"></i>
                                {{
                                    $gettext(
                                        "If set, this prefix will be automatically added to the branch name in the create GitLab branch action."
                                    )
                                }}
                            </p>
                        </div>
                        <div
                            class="tlp-form-element gitlab-configuration-branch-name-prefix"
                            v-bind:class="{
                                'tlp-form-element-disabled': !is_branch_name_prefix_toggled,
                            }"
                            data-test="branch-name-prefix-form-element"
                        >
                            <label class="tlp-label" for="gitlab_server">
                                {{ $gettext("Prefix") }}
                                <i
                                    v-if="is_branch_name_prefix_toggled"
                                    class="fas fa-asterisk"
                                    aria-hidden="true"
                                    data-test="branch-name-prefix-required-flag"
                                ></i>
                            </label>
                            <input
                                type="text"
                                class="tlp-input"
                                size="40"
                                v-bind:required="is_branch_name_prefix_toggled"
                                v-model="branch_name_prefix"
                                data-test="branch-name-prefix-input"
                            />
                        </div>
                        <div class="gitlab-configuration-step-action-buttons">
                            <router-link
                                v-bind:to="{ name: STEP_GITLAB_GROUP }"
                                class="tlp-button-primary tlp-button-outline"
                            >
                                <i class="fas fa-arrow-left tlp-button-icon" aria-hidden="true"></i>
                                {{ $gettext("Back") }}
                            </router-link>
                            <button
                                type="submit"
                                class="tlp-button-primary gitlab-configuration-step-button-submit"
                                v-bind:disabled="is_synchronization_disabled"
                                data-test="gitlab-configuration-submit-button"
                            >
                                {{ $gettext("Link group and synchronize") }}
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
import { STEP_GITLAB_GROUP, STEP_GITLAB_CONFIGURATION } from "../types";
import GitlabGroupLinkWizard from "./GitlabGroupLinkWizard.vue";

const is_artifact_closure_allowed = ref(false);
const uses_branch_name_prefix = ref(false);
const branch_name_prefix = ref("");

const is_branch_name_prefix_toggled = computed(() => uses_branch_name_prefix.value === true);
const is_synchronization_disabled = computed(
    () => uses_branch_name_prefix.value === true && branch_name_prefix.value === ""
);
</script>

<style scoped lang="scss">
.gitlab-configuration-step-action-buttons {
    display: flex;
    margin: var(--tlp-large-spacing) 0 0;
}

.gitlab-configuration-step-button-submit {
    margin: 0 0 0 var(--tlp-medium-spacing);
}

.gitlab-configuration-branch-name-prefix {
    margin: 0 0 0 19px;
}
</style>
