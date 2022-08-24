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
        <gitlab-group-link-wizard v-bind:active_step_id="STEP_GITLAB_SERVER" />

        <div class="tlp-framed-vertically">
            <h1>{{ $gettext("GitLab server") }}</h1>
            <section class="tlp-pane">
                <form class="tlp-pane-container">
                    <section class="tlp-pane-section">
                        <div class="tlp-form-element">
                            <label class="tlp-label" for="gitlab_server">
                                {{ $gettext("GitLab server URL") }}
                                <i class="fas fa-asterisk" aria-hidden="true"></i>
                            </label>
                            <input
                                type="url"
                                class="tlp-input"
                                id="gitlab_server"
                                required
                                placeholder="https://example.com"
                                pattern="https://.+"
                                maxlength="255"
                                size="40"
                            />
                        </div>

                        <div class="tlp-form-element">
                            <label class="tlp-label" for="gitlab_project_token">
                                {{ $gettext("GitLab access token (personal or group)") }}
                                <i class="fas fa-asterisk" aria-hidden="true"></i>
                            </label>
                            <input
                                type="password"
                                class="tlp-input"
                                id="gitlab_project_token"
                                required
                                maxlength="255"
                                autocomplete="off"
                                size="40"
                            />
                            <p class="tlp-text-info gitlab-test-info-form-token-modal">
                                {{
                                    $gettext(
                                        "The access token will be used to fetch repositories, configure project hooks and automatically write comments on GitLab commit and merge requests. It will also be used to extract references to Tuleap from GitLab tags."
                                    )
                                }}
                            </p>
                            <p class="tlp-text-info">
                                {{
                                    $gettext(
                                        "GitLab access token scope must contain at least: api."
                                    )
                                }}
                            </p>
                        </div>
                        <div class="gitlab-server-step-action-buttons">
                            <router-link
                                v-bind:to="{ name: NO_GROUP_LINKED_EMPTY_STATE }"
                                type="submit"
                                class="tlp-button-primary tlp-button-outline"
                            >
                                <i class="fas fa-arrow-left tlp-button-icon" aria-hidden="true"></i>
                                {{ $gettext("Cancel") }}
                            </router-link>
                            <button
                                type="submit"
                                class="tlp-button-primary gitlab-server-step-button-submit"
                                disabled
                            >
                                {{ $gettext("Fetch GitLab groups") }}
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
import { STEP_GITLAB_SERVER, NO_GROUP_LINKED_EMPTY_STATE } from "../types";
import GitlabGroupLinkWizard from "./GitlabGroupLinkWizard.vue";
</script>

<style scoped lang="scss">
.gitlab-server-step-action-buttons {
    display: flex;
    margin: var(--tlp-large-spacing) 0 0;
}

.gitlab-server-step-button-submit {
    margin: 0 0 0 var(--tlp-medium-spacing);
}
</style>
