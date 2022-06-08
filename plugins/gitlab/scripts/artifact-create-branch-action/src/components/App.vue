<!--
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
    <div
        class="modal fade hide"
        id="create-gitlab-branch-artifact-modal"
        role="dialog"
        aria-labelledby="modal-artifact-create-gitlab-branch-choose-integrations"
        aria-hidden="true"
    >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="tuleap-modal-close close" data-dismiss="modal">
                        <i class="fas fa-times modal-close-icon" aria-hidden="true"></i>
                    </i>
                    <h3
                        class="modal-title"
                        id="modal-artifact-create-gitlab-branch-choose-integrations"
                    >
                        <translate class="modal-move-artifact-icon-title">
                            Create branch on a GitLab repository
                        </translate>
                    </h3>
                </div>
                <p v-if="error_message !== ''" class="feedback_error branch-creation-error">
                    {{ error_message }}
                </p>
                <form v-on:submit.prevent="onClickCreateBranch">
                    <div class="modal-body artifact-create-gitlab-branch-modal-body">
                        <div class="artifact-create-gitlab-branch-form-block">
                            <label
                                for="artifact-create-gitlab-branch-select-integration"
                                v-translate
                            >
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
                                    Create a merge request based on this new branch to the default
                                    branch
                                </translate>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="reset" class="btn btn-secondary" data-dismiss="modal">
                            <translate>Close</translate>
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            v-bind:disabled="is_creating_branch"
                        >
                            <i
                                aria-hidden="true"
                                v-if="is_creating_branch"
                                class="fas fa-spin fa-spinner"
                            ></i>
                            <translate>Create branch</translate>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import jquery from "jquery";
import { postGitlabBranch, postGitlabMergeRequest } from "../api/rest-querier";
import * as codendi from "codendi";
import type { GitlabIntegrationWithDefaultBranch } from "../fetch-gitlab-repositories-information";

@Component
export default class App extends Vue {
    @Prop({ required: true, type: Array })
    private readonly integrations!: ReadonlyArray<GitlabIntegrationWithDefaultBranch>;

    @Prop({ required: true, type: String })
    private readonly branch_name!: string;

    @Prop({ required: true, type: Number })
    private readonly artifact_id!: number;

    private selected_integration: GitlabIntegrationWithDefaultBranch | null = null;
    private reference = "";
    private is_creating_branch = false;
    private must_create_gitlab_mr = false;
    private error_message = "";

    mounted(): void {
        const jquery_element = jquery(this.$el);
        jquery_element.on("hidden", () => {
            this.$root.$destroy();
            this.$root.$el.parentNode?.removeChild(this.$root.$el);
        });
        this.selected_integration = this.integrations[0];
        jquery_element.modal();
    }

    get branch_name_placeholder(): string {
        let placeholder = this.branch_name;
        if (this.selected_integration) {
            placeholder = this.selected_integration.create_branch_prefix + this.branch_name;
        }
        return placeholder;
    }

    @Watch("selected_integration")
    onSelectedIntegration(integration: GitlabIntegrationWithDefaultBranch | null): void {
        this.reference = integration?.default_branch ?? "";
    }

    async onClickCreateBranch(): Promise<void> {
        const integration = this.selected_integration;
        if (integration === null) {
            return;
        }
        this.is_creating_branch = true;
        const create_branch_result = postGitlabBranch(
            integration.id,
            this.artifact_id,
            this.reference
        );
        await create_branch_result.match(
            async (branch) => {
                let success_message = this.$gettextInterpolate(
                    this.$gettext(
                        'The branch <a href="%{ branch_url }">%{ branch_name }</a> has been successfully created on <a href="%{ repo_url }">%{ repo_name }</a>'
                    ),
                    {
                        branch_name: branch.branch_name,
                        branch_url:
                            integration.gitlab_repository_url + "/-/tree/" + branch.branch_name, // The branch name is not escaped in the URL on purpose: GL expects it raw
                        repo_url: integration.gitlab_repository_url,
                        repo_name: integration.name,
                    }
                );

                codendi.feedback.log("info", success_message);

                if (this.must_create_gitlab_mr) {
                    const create_merge_request_result = postGitlabMergeRequest(
                        integration.id,
                        this.artifact_id,
                        branch.branch_name
                    );
                    await create_merge_request_result.match(
                        () => {
                            codendi.feedback.log(
                                "info",
                                this.$gettext("The associated merge request has been created.")
                            );
                        },
                        async (error_promise) => {
                            const merge_error = await error_promise;
                            this.is_creating_branch = false;

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
                                    this.$gettext(
                                        "An error occurred while creating the associated merge request."
                                    )
                                );
                                throw merge_error.error_message;
                            }
                        }
                    );
                }

                jquery(this.$el).modal("hide");
            },
            async (error_promise) => {
                const error = await error_promise;
                this.is_creating_branch = false;

                if (
                    Object.prototype.hasOwnProperty.call(error, "i18n_error_message") &&
                    error.i18n_error_message
                ) {
                    this.error_message = error.i18n_error_message;
                } else {
                    this.error_message = this.$gettextInterpolate(
                        this.$gettext(
                            "An error occurred while creating %{ branch_name }, please try again"
                        ),
                        { branch_name: this.branch_name }
                    );
                    throw error.error_message;
                }
            }
        );
    }
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

.branch-creation-error {
    margin: var(--tlp-medium-spacing);
}
</style>
