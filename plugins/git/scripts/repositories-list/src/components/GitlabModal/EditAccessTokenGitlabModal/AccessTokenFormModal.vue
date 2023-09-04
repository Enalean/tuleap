<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -->

<template>
    <form v-on:submit="addGitlabToken" data-test="edit-token-gitlab-repository-modal-form">
        <div class="tlp-modal-body">
            <p>
                {{ tokenAPIInformationMessage() }}
            </p>
            <div
                class="tlp-alert-danger"
                data-test="gitlab-fail-check-new-token"
                v-if="error_message.length > 0"
            >
                {{ error_message }}
            </div>
            <div class="tlp-property">
                <label class="tlp-label">
                    <translate>GitLab server URL</translate>
                </label>
                <p>{{ instance_url }}</p>
            </div>
            <div class="tlp-property">
                <label class="tlp-label">
                    <translate>GitLab repository</translate>
                </label>
                <p>{{ repository.normalized_path }}</p>
            </div>

            <div
                class="tlp-form-element"
                v-bind:class="{ 'tlp-form-element-error': error_message.length > 0 }"
            >
                <label class="tlp-label" for="gitlab_new_token">
                    <translate>GitLab access token (personal or project)</translate>
                    <i class="fas fa-asterisk" aria-hidden="true"></i>
                </label>
                <input
                    type="password"
                    class="tlp-input"
                    id="gitlab_new_token"
                    required
                    v-model="gitlab_new_token"
                    data-test="add_gitlab_new_token"
                    autocomplete="off"
                />
                <p class="tlp-text-info">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <translate>GitLab access token scope must contain at least: api.</translate>
                </p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-on:click="cancelButton"
                data-test="button-cancel-new-token-gitlab-repository"
            >
                <translate>Cancel</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="disabled_button"
                data-test="button-check-new-token-gitlab-repository"
            >
                <i
                    v-if="is_checking_validity_of_new_token"
                    class="fas tlp-button-icon fa-spin fa-circle-notch"
                    data-test="icon-spin"
                    aria-hidden="true"
                ></i>
                <translate>Check new token validity</translate>
                <i class="fas tlp-button-icon-right fa-long-arrow-alt-right" aria-hidden="true"></i>
            </button>
        </div>
    </form>
</template>

<script lang="ts">
import { credentialsAreEmpty } from "../../../gitlab/gitlab-credentials-helper";
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import type { GitLabCredentials, GitLabRepository, Repository } from "../../../type";
import { namespace } from "vuex-class";

const gitlab = namespace("gitlab");

@Component
export default class AccessTokenFormModal extends Vue {
    @Prop({ required: true })
    readonly repository!: Repository;

    @Prop({ required: true })
    readonly gitlab_token!: string;

    @gitlab.Action
    readonly getGitlabRepositoryFromId!: ({
        credentials,
        id,
    }: {
        credentials: GitLabCredentials;
        id: number;
    }) => Promise<GitLabRepository>;

    gitlab_new_token = this.gitlab_token;
    error_message = "";
    is_checking_validity_of_new_token = false;

    get instance_url(): string {
        if (!this.repository.gitlab_data || !this.repository.normalized_path) {
            return "";
        }
        return this.repository.gitlab_data.gitlab_repository_url.replace(
            this.repository.normalized_path,
            "",
        );
    }

    get disabled_button(): boolean {
        return this.gitlab_new_token === "" || this.is_checking_validity_of_new_token;
    }

    tokenAPIInformationMessage(): string {
        return this.$gettext(
            "The access token will be used to configure project hooks and automatically write comments on GitLab commits and merge requests. It's also needed to be able to extract Tuleap references from GitLab tag message.",
        );
    }

    resetErrorMessage(): void {
        this.error_message = "";
    }

    reset(): void {
        this.gitlab_new_token = "";
        this.is_checking_validity_of_new_token = false;
        this.resetErrorMessage();
    }

    cancelButton(): void {
        this.reset();
        this.$emit("on-close-modal");
    }

    async addGitlabToken(event: Event): Promise<void> {
        event.preventDefault();
        this.resetErrorMessage();

        const credentials = {
            server_url: this.instance_url,
            token: this.gitlab_new_token,
        };

        if (credentialsAreEmpty(credentials)) {
            this.error_message = this.$gettext("You must provide a valid GitLab API token");
            return;
        }

        try {
            this.is_checking_validity_of_new_token = true;

            if (!this.repository.gitlab_data) {
                return;
            }

            await this.getGitlabRepositoryFromId({
                credentials,
                id: this.repository.gitlab_data.gitlab_repository_id,
            });

            this.$emit("on-get-new-token-gitlab", {
                token: this.gitlab_new_token,
            });
        } catch (e) {
            this.error_message = this.$gettext(
                "Submitted token is invalid to access to this repository on this GitLab server.",
            );
        } finally {
            this.is_checking_validity_of_new_token = false;
        }
    }
}
</script>
