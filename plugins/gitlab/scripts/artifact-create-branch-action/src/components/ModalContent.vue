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
                {{ $gettext("Create branch on a GitLab repository") }}
            </h1>
            <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div v-if="error_message" class="tlp-modal-feedback">
            <div class="feedback_error">
                {{ error_message }}
            </div>
        </div>
        <div class="tlp-modal-body">
            <div class="artifact-create-gitlab-branch-form-block">
                <label for="artifact-create-gitlab-branch-select-integration">
                    {{ $gettext("GitLab repositories integrations") }}
                    <span
                        class="artifact-create-branch-action-mandatory-information"
                        aria-hidden="true"
                    >
                        *
                    </span>
                </label>
                <select
                    id="artifact-create-gitlab-branch-select-integration"
                    required
                    aria-required="true"
                    v-model="selected_integration"
                    data-test="integrations-select"
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
                <label for="artifact-create-gitlab-branch-reference">
                    {{ $gettext("Git reference from where the branch should be created") }}
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
                    required
                    aria-required="true"
                    v-model="reference"
                    data-test="branch-reference-input"
                />
                <p class="text-info">
                    {{ $gettext("Must be an existing git commit SHA-1 or a branch name") }}
                </p>
            </div>
            <div>
                <label for="artifact-create-gitlab-branch-name">
                    {{ $gettext("The following branch will be created") }}
                </label>
                <code id="artifact-create-gitlab-branch-name">
                    {{ branch_name_placeholder }}
                </code>
            </div>
            <div class="artifact-create-gitlab-merge-request">
                <label class="tlp-label tlp-checkbox">
                    <input
                        type="checkbox"
                        v-model="must_create_gitlab_mr"
                        data-test="create-merge-request-checkbox"
                    />
                    {{
                        $gettext(
                            "Create a merge request based on this new branch to the default branch",
                        )
                    }}
                </label>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_creating_branch"
                v-on:click="onClickCreateBranch"
                data-test="create-branch-submit-button"
            >
                <i
                    aria-hidden="true"
                    v-if="is_creating_branch"
                    class="fas fa-spin fa-spinner tlp-button-icon"
                ></i>
                {{ update_button_label }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { GitLabIntegrationCreatedBranchInformation } from "../api/rest-querier";
import { postGitlabBranch, postGitlabMergeRequest } from "../api/rest-querier";
import type { GitlabIntegrationWithDefaultBranch } from "../fetch-gitlab-repositories-information";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { useGettext } from "vue3-gettext";
import { okAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { BranchCreationFault } from "../BranchCreationFault";
import { MergeRequestCreationFault } from "../MergeRequestCreationFault";
import { addFeedback } from "@tuleap/fp-feedback";

const { interpolate, $gettext } = useGettext();

const root_element = ref<InstanceType<typeof Element>>();

const props = defineProps<{
    integrations: ReadonlyArray<GitlabIntegrationWithDefaultBranch>;
    branch_name: string;
    artifact_id: number;
}>();

const is_creating_branch = ref(false);
const must_create_gitlab_mr = ref(true);
const error_message = ref("");
const reference = ref("");
let modal: Modal | null = null;

const selected = ref(props.integrations[0]);
let selected_integration = computed({
    get(): GitlabIntegrationWithDefaultBranch {
        return selected.value;
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

const update_button_label = computed((): string =>
    must_create_gitlab_mr.value
        ? $gettext("Create branch and merge request")
        : $gettext("Create branch"),
);

const createMergeRequest = (
    integration: GitlabIntegrationWithDefaultBranch,
    branch: GitLabIntegrationCreatedBranchInformation,
): ResultAsync<void, Fault> =>
    postGitlabMergeRequest(integration.id, props.artifact_id, branch.branch_name)
        .map(() => {
            addFeedback("info", $gettext("The associated merge request has been created."));
        })
        .mapErr(MergeRequestCreationFault.fromFault);

const isBranchCreationFault = (fault: Fault): boolean =>
    "isBranchCreationFault" in fault && fault.isBranchCreationFault() === true;
const isMergeRequestCreationFault = (fault: Fault): boolean =>
    "isMergeRequestCreationFault" in fault && fault.isMergeRequestCreationFault() === true;

function onClickCreateBranch(): Promise<void> {
    const integration = selected.value;
    is_creating_branch.value = true;
    return postGitlabBranch(integration.id, props.artifact_id, reference.value)
        .mapErr(BranchCreationFault.fromFault)
        .andThen((branch) => {
            const success_message = interpolate(
                $gettext(
                    'The branch <a href="%{ branch_url }">%{ branch_name }</a> has been successfully created on <a href="%{ repo_url }">%{ repo_name }</a>',
                ),
                {
                    branch_name: branch.branch_name,
                    branch_url: integration.gitlab_repository_url + "/-/tree/" + branch.branch_name, // The branch name is not escaped in the URL on purpose: GL expects it raw
                    repo_url: integration.gitlab_repository_url,
                    repo_name: integration.name,
                },
            );

            addFeedback("info", success_message);

            if (!must_create_gitlab_mr.value) {
                return okAsync("irrelevant return");
            }
            return createMergeRequest(integration, branch);
        })
        .match(
            () => {
                is_creating_branch.value = false;
                modal?.hide();
            },
            (fault) => {
                is_creating_branch.value = false;
                if (isBranchCreationFault(fault)) {
                    error_message.value = interpolate(
                        $gettext("An error occurred while creating %{ branch_name }: %{ error }"),
                        { branch_name: props.branch_name, error: String(fault) },
                    );
                    return;
                }
                if (isMergeRequestCreationFault(fault)) {
                    error_message.value = interpolate(
                        $gettext(
                            "An error occurred while creating the associated merge request: %{ error }",
                        ),
                        { error: String(fault) },
                    );
                    return;
                }
                error_message.value = String(fault);
            },
        );
}
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
</style>
