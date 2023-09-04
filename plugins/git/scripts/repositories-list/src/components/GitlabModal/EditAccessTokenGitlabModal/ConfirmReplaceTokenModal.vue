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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -->

<template>
    <div>
        <div class="tlp-modal-body">
            <div
                class="tlp-alert-danger"
                data-test="gitlab-fail-patch-edit-token"
                v-if="have_any_rest_error"
            >
                {{ message_error_rest }}
            </div>
            <div>
                <p>
                    {{ confirmation_message }}
                </p>
                <translate tag="p">Please confirm your action.</translate>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-test="button-gitlab-edit-token-back"
                v-on:click="onBackToEdit"
            >
                <i class="fas fa-long-arrow-alt-left tlp-button-icon" aria-hidden="true"></i>
                <translate>Back</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                data-test="button-confirm-edit-token-gitlab"
                v-on:click="confirmEditToken"
                v-bind:disabled="disabled_button"
            >
                <i
                    v-if="is_patching_new_token"
                    class="fas tlp-button-icon fa-spin fa-circle-notch"
                    data-test="icon-spin"
                    aria-hidden="true"
                ></i>
                <translate>Save new token</translate>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import type { GitLabDataWithTokenPayload, Repository } from "../../../type";
import Vue from "vue";
import { namespace } from "vuex-class";
import { handleError } from "../../../gitlab/gitlab-error-handler";

const gitlab = namespace("gitlab");

@Component
export default class ConfirmReplaceTokenModal extends Vue {
    @Prop({ required: true })
    readonly repository!: Repository;

    @Prop({ required: true })
    readonly gitlab_new_token!: string;

    @gitlab.Action
    readonly updateBotApiTokenGitlab!: (gitlab_data: GitLabDataWithTokenPayload) => Promise<void>;

    message_error_rest = "";
    is_patching_new_token = false;

    get confirmation_message(): string {
        return this.$gettextInterpolate(
            this.$gettext(
                "You are about to update the token used to integrate %{ label } repository of %{ instance_url }.",
            ),
            {
                label: this.repository.normalized_path,
                instance_url: this.instance_url,
            },
        );
    }

    get instance_url(): string {
        if (!this.repository.gitlab_data || !this.repository.normalized_path) {
            return "";
        }
        return this.repository.gitlab_data.gitlab_repository_url.replace(
            this.repository.normalized_path,
            "",
        );
    }

    get have_any_rest_error(): boolean {
        return this.message_error_rest.length > 0;
    }

    get disabled_button(): boolean {
        return this.is_patching_new_token || this.have_any_rest_error;
    }

    onBackToEdit(): void {
        this.reset();
        this.$emit("on-back-button");
    }

    reset(): void {
        this.is_patching_new_token = false;
        this.message_error_rest = "";
    }

    async confirmEditToken(event: Event): Promise<void> {
        event.preventDefault();

        if (this.have_any_rest_error) {
            return;
        }

        if (!this.repository.gitlab_data) {
            return;
        }

        this.is_patching_new_token = true;

        try {
            await this.updateBotApiTokenGitlab({
                gitlab_integration_id: this.repository.integration_id,
                gitlab_api_token: this.gitlab_new_token,
            });

            this.$emit("on-success-edit-token");
        } catch (rest_error) {
            this.message_error_rest = await handleError(rest_error, this);
            throw rest_error;
        } finally {
            this.is_patching_new_token = false;
        }
    }
}
</script>
