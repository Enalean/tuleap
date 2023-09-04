<!--
  - Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
    <div ref="root_element" class="tlp-modal" role="dialog">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title">
                {{ $gettext("Create branch on a Git repository") }}
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
            <div class="form-block">
                <label for="artifact-create-git-branch-select-repository">
                    {{ $gettext("Project Git repositories") }}
                    <span class="action-mandatory-information" aria-hidden="true">*</span>
                </label>
                <select
                    id="artifact-create-git-branch-select-repository"
                    required
                    aria-required="true"
                    v-model="selected_repository"
                    data-test="repositories-select"
                >
                    <option
                        v-for="repository in repositories"
                        v-bind:value="repository"
                        v-bind:key="repository.id"
                    >
                        {{ repository.name }}
                    </option>
                </select>
            </div>
            <div class="form-block">
                <label for="artifact-create-git-branch-reference">
                    {{ $gettext("Git reference from where the branch should be created") }}
                    <span class="action-mandatory-information" aria-hidden="true">*</span>
                </label>
                <input
                    type="text"
                    id="artifact-create-git-branch-reference"
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
            <p>
                <label for="artifact-create-git-branch-name">
                    {{ $gettext("The following branch will be created") }}
                </label>
                <code id="artifact-create-git-branch-name">
                    {{ branch_name_preview }}
                </code>
            </p>
            <p v-if="are_pullrequest_endpoints_available">
                <label class="tlp-label tlp-checkbox">
                    <input
                        type="checkbox"
                        v-model="must_create_pr"
                        data-test="create-pr-checkbox"
                    />
                    {{
                        $gettext(
                            "Create a pull request based on this new branch to the default branch",
                        )
                    }}
                </label>
            </p>
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
                {{ button_label }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { GitRepository } from "../types";
import { postGitBranch, postPullRequestOnDefaultBranch } from "../../api/rest_querier";
import { addFeedback } from "@tuleap/fp-feedback";
import { useGettext } from "vue3-gettext";
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { PullRequestCreationFault } from "../PullRequestCreationFault";

let modal: Modal | null = null;
const { $gettext, interpolate } = useGettext();
const root_element = ref<InstanceType<typeof Element>>();
const reference = ref("");
const error_message = ref("");

const props = defineProps<{
    repositories: ReadonlyArray<GitRepository>;
    branch_name_preview: string;
    are_pullrequest_endpoints_available: boolean;
}>();

const is_creating_branch = ref(false);
const selected = ref(props.repositories[0]);
let selected_repository = computed({
    get(): GitRepository {
        return selected.value;
    },
    set(value: GitRepository) {
        reference.value = value.default_branch ?? "";
        selected.value = value;
    },
});

const must_create_pr = ref(props.are_pullrequest_endpoints_available);
const button_label = computed((): string =>
    must_create_pr.value ? $gettext("Create branch and pull request") : $gettext("Create branch"),
);

onMounted((): void => {
    if (root_element.value === undefined) {
        throw new Error("Cannot find modal root element");
    }
    modal = createModal(root_element.value, {
        keyboard: false,
        dismiss_on_backdrop_click: false,
        destroy_on_hide: true,
    });

    reference.value = props.repositories[0]?.default_branch;

    modal.show();
});

onBeforeUnmount(() => {
    modal?.destroy();
});

const createPullRequest = (
    repository: GitRepository,
    branch_name: string,
): ResultAsync<void, Fault> =>
    postPullRequestOnDefaultBranch(repository, branch_name)
        .map(() => {
            addFeedback("info", $gettext("The associated pull request has been created."));
        })
        .mapErr(PullRequestCreationFault.fromFault);

const isPullRequestCreationFault = (fault: Fault): boolean =>
    "isPullRequestCreationFault" in fault && fault.isPullRequestCreationFault() === true;

function onClickCreateBranch(): Promise<void> {
    is_creating_branch.value = true;
    const repository: GitRepository = selected.value;
    return postGitBranch(repository.id, props.branch_name_preview, reference.value)
        .andThen((created_branch) => {
            const success_message = interpolate(
                $gettext(
                    'The branch <a href="%{ branch_url }">%{ branch_name }</a> has been successfully created on <a href="%{ repo_url }">%{ repo_name }</a>',
                ),
                {
                    branch_name: props.branch_name_preview,
                    branch_url: created_branch.html_url,
                    repo_url: repository.html_url,
                    repo_name: repository.name,
                },
            );

            addFeedback("info", success_message);

            if (!must_create_pr.value) {
                return okAsync("branch created");
            }
            return createPullRequest(repository, props.branch_name_preview);
        })
        .match(
            () => {
                is_creating_branch.value = false;
                modal?.hide();
            },
            (fault) => {
                if (isPullRequestCreationFault(fault)) {
                    error_message.value = interpolate(
                        $gettext(
                            "An error occurred while creating the associated pull request: %{ error }",
                        ),
                        { error: String(fault) },
                    );
                } else {
                    error_message.value = interpolate(
                        $gettext(
                            "An error occurred while creating the Git branch %{ branch_name }: %{ error }",
                        ),
                        { branch_name: props.branch_name_preview, error: String(fault) },
                    );
                }
                is_creating_branch.value = false;
            },
        );
}
</script>

<style lang="scss" scoped>
.action-mandatory-information {
    color: var(--tlp-danger-color);
}

.form-block {
    margin: 0 0 var(--tlp-medium-spacing);
}
</style>
