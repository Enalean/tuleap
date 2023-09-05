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
    <div role="dialog" aria-labelledby="regenerate-gitlab-webhook" class="tlp-modal">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title">
                <translate id="regenerate-gitlab-webhook">Regenerate the GitLab webhook</translate>
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close_label"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div
            class="tlp-modal-feedback"
            v-if="have_any_rest_error"
            data-test="regenerate-gitlab-webhook-fail"
        >
            <div class="tlp-alert-danger">
                {{ message_error_rest }}
            </div>
        </div>
        <div class="tlp-modal-body" v-if="repository !== null">
            <translate tag="p">
                Regenerate the GitLab webhook will invalidate the previous webhook, and create a new
                one with a new secret. Webhook is used to allow GitLab to securely communicate with
                Tuleap whenever something happen in the repository (e.g. push commits, new merge
                requests, ...).
            </translate>
            <translate
                tag="p"
                v-bind:translate-params="{
                    label: repository.normalized_path,
                    instance_url: instance_url,
                }"
            >
                You are about to regenerate the webhook for %{ label } repository located at %{
                instance_url }.
            </translate>
            <translate tag="p">Please confirm your action.</translate>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                data-test="regenerate-gitlab-webhook-cancel"
            >
                <translate>Cancel</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                data-test="regenerate-gitlab-webhook-submit"
                v-on:click="confirmRegenerateWebhookGitlab"
                v-bind:disabled="disabled_button"
            >
                <i
                    v-if="is_updating_webhook"
                    class="fas fa-spin fa-circle-notch tlp-button-icon"
                    data-test="icon-spin"
                ></i>
                <translate>Regenerate webhook</translate>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import { Component } from "vue-property-decorator";
import Vue from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { Repository } from "../../../type";
import { namespace } from "vuex-class";
import { handleError } from "../../../gitlab/gitlab-error-handler";

const gitlab = namespace("gitlab");

@Component
export default class RegenerateGitlabWebhook extends Vue {
    @gitlab.Action
    readonly regenerateGitlabWebhook!: (integration_id: number | string) => Promise<void>;

    @gitlab.State
    readonly regenerate_gitlab_webhook_repository!: Repository;

    private modal: Modal | null = null;
    repository: Repository | null = null;
    is_updating_webhook = false;
    message_error_rest = "";

    get close_label(): string {
        return this.$gettext("Close");
    }

    mounted(): void {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-shown", this.onShownModal);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.$store.commit("gitlab/setRegenerateGitlabWebhookModal", this.modal);
    }

    onShownModal(): void {
        this.repository = this.regenerate_gitlab_webhook_repository;
    }

    reset(): void {
        this.is_updating_webhook = false;
        this.repository = null;
        this.message_error_rest = "";
    }

    onSuccessRegenerateGitlabWebhook(): void {
        this.$store.commit("setSuccessMessage", this.success_message);
    }

    get success_message(): string {
        if (!this.repository || !this.repository.normalized_path) {
            return "";
        }

        return this.$gettextInterpolate(
            this.$gettext(
                "New webhook of GitLab repository %{ label } has been successfully regenerated.",
            ),
            {
                label: this.repository.normalized_path,
            },
        );
    }

    get disabled_button() {
        return this.is_updating_webhook || this.have_any_rest_error;
    }

    get instance_url(): string {
        if (!this.repository || !this.repository.gitlab_data || !this.repository.normalized_path) {
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

    async confirmRegenerateWebhookGitlab(event: Event): Promise<void> {
        event.preventDefault();

        if (this.have_any_rest_error) {
            return;
        }

        if (!this.repository) {
            return;
        }

        if (!this.repository.gitlab_data) {
            return;
        }

        this.is_updating_webhook = true;

        try {
            await this.regenerateGitlabWebhook(this.repository.integration_id);
            this.repository.gitlab_data.is_webhook_configured = true;
            this.onSuccessRegenerateGitlabWebhook();
            if (this.modal) {
                this.modal.hide();
            }
        } catch (rest_error) {
            this.message_error_rest = await handleError(rest_error, this);
            throw rest_error;
        } finally {
            this.is_updating_webhook = false;
        }
    }
}
</script>
