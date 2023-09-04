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
            <h2>{{ $gettext("GitLab server") }}</h2>
            <div
                v-if="error_message"
                class="tlp-alert-danger"
                data-test="gitlab-server-fetch-error"
            >
                {{ error_message }}
            </div>
            <section class="tlp-pane">
                <form ref="form" class="tlp-pane-container">
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
                                v-model="gitlab_server_url"
                                data-test="gitlab-server-url"
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
                                v-model="gitlab_access_token"
                                data-test="gitlab-access-token"
                            />
                            <p class="tlp-text-info gitlab-test-info-form-token-modal">
                                {{
                                    $gettext(
                                        "The access token will be used to fetch repositories, configure project hooks and automatically write comments on GitLab commit and merge requests. It will also be used to extract references to Tuleap from GitLab tags.",
                                    )
                                }}
                            </p>
                            <p class="tlp-text-info">
                                {{
                                    $gettext(
                                        "GitLab access token scope must contain at least: api.",
                                    )
                                }}
                            </p>
                        </div>
                        <div class="gitlab-server-step-action-buttons">
                            <button
                                type="button"
                                v-on:click="onClickCancel"
                                class="tlp-button-primary tlp-button-outline"
                                data-test="gitlab-group-link-cancel-button"
                            >
                                <i class="fas fa-arrow-left tlp-button-icon" aria-hidden="true"></i>
                                {{ $gettext("Cancel") }}
                            </button>
                            <button
                                type="submit"
                                class="tlp-button-primary gitlab-server-step-button-submit"
                                v-bind:disabled="is_fetching_gitlab_groups_disabled"
                                v-on:click="onClickFetchGitLabGroups"
                                data-test="gitlab-fetch-groups-button"
                            >
                                {{ $gettext("Fetch GitLab groups") }}
                                <i
                                    class="fas fa-arrow-right tlp-button-icon tlp-button-icon-right"
                                    v-bind:class="{
                                        'fas fa-arrow-right': !is_fetching_groups,
                                        'fas fa-spin fa-circle-notch': is_fetching_groups,
                                    }"
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
import { useGettext } from "vue3-gettext";
import { useRouter } from "vue-router";
import type { Fault } from "@tuleap/fault";

import { STEP_GITLAB_SERVER, STEP_GITLAB_GROUP, NO_GROUP_LINKED_EMPTY_STATE } from "../types";
import GitlabGroupLinkWizard from "./GitlabGroupLinkWizard.vue";
import { createGitlabApiQuerier } from "../api/gitlab-api-querier";
import type { GitlabGroup } from "../stores/types";
import { useGitLabGroupsStore } from "../stores/groups";
import { useCredentialsStore } from "../stores/credentials";

const { $gettext, interpolate } = useGettext();
const gitlab_api_querier = createGitlabApiQuerier();
const credentials_store = useCredentialsStore();
const groups_store = useGitLabGroupsStore();
const router = useRouter();

const gitlab_server_url = ref(credentials_store.credentials.server_url);
const gitlab_access_token = ref(credentials_store.credentials.token);
const error_message = ref("");
const is_fetching_groups = ref(false);
const form: Ref<HTMLFormElement | null> = ref(null);

const is_fetching_gitlab_groups_disabled = computed(
    () => !gitlab_server_url.value || !gitlab_access_token.value || is_fetching_groups.value,
);

const isNetworkFault = (fault: Fault): boolean =>
    "isNetworkFault" in fault && fault.isNetworkFault() === true;
const isGitLabCredentialsFault = (fault: Fault): boolean =>
    "isUnauthenticated" in fault && fault.isUnauthenticated() === true;

function onClickFetchGitLabGroups(event: Event): void {
    event.preventDefault();
    if (form.value === null) {
        throw new Error("Cannot find the gitlab server form");
    }

    if (!form.value.checkValidity()) {
        form.value.reportValidity();
        return;
    }

    let server_url = null;
    try {
        server_url = new URL(gitlab_server_url.value);
    } catch (error) {
        form.value.reportValidity();
        return;
    }

    error_message.value = "";
    is_fetching_groups.value = true;

    gitlab_api_querier
        .getGitlabGroups({
            server_url,
            token: gitlab_access_token.value,
        })
        .match(
            (groups: readonly GitlabGroup[]) => {
                groups_store.setGroups(groups);
                credentials_store.setCredentials({
                    server_url: new URL(gitlab_server_url.value),
                    token: gitlab_access_token.value,
                });
                router.push({ name: STEP_GITLAB_GROUP });
            },
            (fault) => {
                if (isNetworkFault(fault)) {
                    error_message.value = $gettext(
                        "Network error while fetching the GitLab groups. Please verify your GitLab server url validity.",
                    );
                    return;
                }

                if (isGitLabCredentialsFault(fault)) {
                    error_message.value = $gettext(
                        "Unable to connect to the GitLab server, please check your credentials.",
                    );

                    return;
                }

                error_message.value = interpolate(
                    $gettext("Unable to reach the GitLab server: %{ error }"),
                    { error: String(fault) },
                    true,
                );
            },
        )
        .finally(() => {
            is_fetching_groups.value = false;
        });
}

function onClickCancel(): void {
    credentials_store.$reset();
    groups_store.$reset();

    router.push({ name: NO_GROUP_LINKED_EMPTY_STATE });
}
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
