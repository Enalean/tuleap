<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
  -
  -->

<template>
    <div
        ref="root_element"
        class="tlp-modal"
        role="dialog"
        aria-labelledby="modal-artifact-create-gitlab-branch-choose-integrations"
    >
        <div class="tlp-modal-header">
            <h1
                class="tlp-modal-title"
                id="modal-artifact-create-gitlab-branch-choose-integrations"
            >
                <translate>Create branch on a GitLab repository</translate>
            </h1>
            <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>

        <p v-if="error_message" class="feedback_error branch-creation-error">
            {{ error_message }}
        </p>
        <div class="tlp-modal-body">
            <div class="artifact-create-gitlab-branch-form-block">
                <label for="artifact-create-gitlab-branch-select-integration" v-translate>
                    GitLab repositories integrations
                    <span
                        class="artifact-create-branch-action-mandatory-information"
                        aria-hidden="true"
                    >
                        *
                    </span>
                </label>
                <select
                    id="artifact-create-gitlab-branch-select-integration"
                    required="required"
                    aria-required="true"
                    v-model="selected_integration"
                >
                    <option
                        v-for="integration in integrations"
                        v-bind:value="integration"
                        v-bind:key="integration.id"
                    >
                        {{ integration.name }}
                    </option>
                </select>
            </div>
            <div class="artifact-create-gitlab-branch-form-block">
                <label for="artifact-create-gitlab-branch-reference" v-translate>
                    Git reference from where the branch should be created
                    <span
                        class="artifact-create-branch-action-mandatory-information"
                        aria-hidden="true"
                    >
                        *
                    </span>
                </label>
                <input
                    type="text"
                    id="artifact-create-gitlab-branch-reference"
                    placeholder="main"
                    required="required"
                    aria-required="true"
                    v-model="reference"
                />
                <p class="text-info" v-translate>
                    Must be an existing git commit SHA-1 or a branch name
                </p>
            </div>
            <div>
                <label for="artifact-create-gitlab-branch-name" v-translate>
                    The following branch will be created
                </label>
                <code id="artifact-create-gitlab-branch-name">
                    {{ branch_name_placeholder }}
                </code>
            </div>
            <div class="artifact-create-gitlab-merge-request">
                <label class="tlp-label tlp-checkbox">
                    <input type="checkbox" v-model="must_create_gitlab_mr" />
                    <translate>
                        Create a merge request based on this new branch to the default branch
                    </translate>
                </label>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                <translate>Cancel</translate>
            </button>
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_creating_branch"
                v-on:click="onClickCreateBranch"
            >
                <i
                    aria-hidden="true"
                    v-if="is_creating_branch"
                    class="fas fa-spin fa-spinner tlp-button-icon"
                ></i>
                <translate>Create branch</translate>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { postGitlabBranch, postGitlabMergeRequest } from "../api/rest-querier";
import * as codendi from "codendi";
import type { GitlabIntegrationWithDefaultBranch } from "../fetch-gitlab-repositories-information";
import { computed, ref, onMounted, onBeforeUnmount } from "@vue/composition-api";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";

const { interpolate, $gettext } = useGettext();

const root_element = ref<InstanceType<typeof Element>>();

const props = defineProps<{
    integrations: ReadonlyArray<GitlabIntegrationWithDefaultBranch>;
    branch_name: string;
    artifact_id: number;
}>();

let is_creating_branch = ref(false);
let must_create_gitlab_mr = ref(false);
let error_message = ref("");
let reference = ref("");
let modal: Modal;

const selected = ref(props.integrations[0]);
let selected_integration = computed({
    get(): GitlabIntegrationWithDefaultBranch {
        return props.integrations[0];
    },
    set(value: GitlabIntegrationWithDefaultBranch) {
        reference.value = value.default_branch ?? "";
        selected.value = value;
    },
});

onMounted((): void => {
    if (root_element.value === undefined) {
        throw new Error("Cannot find modal root element");
    }
    modal = createModal(root_element.value, {
        keyboard: false,
        dismiss_on_backdrop_click: false,
        destroy_on_hide: true,
    });

    reference.value = props.integrations[0]?.default_branch;

    modal.show();
});

onBeforeUnmount(() => {
    modal?.destroy();
});

const branch_name_placeholder = computed((): string => {
    let placeholder = props.branch_name;
    if (selected.value) {
        placeholder = selected.value.create_branch_prefix + props.branch_name;
    }
    return placeholder;
});

async function onClickCreateBranch(): Promise<void> {
    const integration = selected.value;
    is_creating_branch.value = true;
    const create_branch_result = postGitlabBranch(
        integration.id,
        props.artifact_id,
        reference.value
    );
    await create_branch_result.match(
        async (branch) => {
            let success_message = interpolate(
                $gettext(
                    'The branch <a href="%{ branch_url }">%{ branch_name }</a> has been successfully created on <a href="%{ repo_url }">%{ repo_name }</a>'
                ),
                {
                    branch_name: branch.branch_name,
                    branch_url: integration.gitlab_repository_url + "/-/tree/" + branch.branch_name, // The branch name is not escaped in the URL on purpose: GL expects it raw
                    repo_url: integration.gitlab_repository_url,
                    repo_name: integration.name,
                }
            );

            codendi.feedback.log("info", success_message);

            if (must_create_gitlab_mr.value) {
                const create_merge_request_result = postGitlabMergeRequest(
                    integration.id,
                    props.artifact_id,
                    branch.branch_name
                );
                await create_merge_request_result.match(
                    () => {
                        codendi.feedback.log(
                            "info",
                            $gettext("The associated merge request has been created.")
                        );
                    },
                    async (error_promise) => {
                        const merge_error = await error_promise;
                        is_creating_branch.value = false;

                        if (
                            Object.prototype.hasOwnProperty.call(
                                merge_error,
                                "i18n_error_message"
                            ) &&
                            merge_error.i18n_error_message
                        ) {
                            codendi.feedback.log("error", merge_error.i18n_error_message);
                        } else {
                            codendi.feedback.log(
                                "error",
                                $gettext(
                                    "An error occurred while creating the associated merge request."
                                )
                            );
                            throw merge_error.error_message;
                        }
                    }
                );
            }

            modal.hide();
        },
        async (error_promise) => {
            const error = await error_promise;
            is_creating_branch.value = false;

            if (
                Object.prototype.hasOwnProperty.call(error, "i18n_error_message") &&
                error.i18n_error_message
            ) {
                error_message.value = error.i18n_error_message;
            } else {
                error_message.value = interpolate(
                    $gettext("An error occurred while creating %{ branch_name }, please try again"),
                    { branch_name: props.branch_name }
                );
                throw error.error_message;
            }
        }
    );
}
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>

<style scoped lang="scss">
.artifact-create-branch-action-mandatory-information {
    color: var(--tlp-danger-color);
}

.artifact-create-gitlab-branch-form-block {
    margin: 0 0 var(--tlp-medium-spacing);
}

.artifact-create-gitlab-merge-request {
    margin: var(--tlp-medium-spacing) 0 0;
}

.branch-creation-error {
    margin: var(--tlp-medium-spacing);
}
</style>
