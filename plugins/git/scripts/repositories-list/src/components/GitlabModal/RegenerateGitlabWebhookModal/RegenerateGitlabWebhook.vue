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
    <div
        role="dialog"
        aria-labelledby="regenerate-gitlab-webhook"
        class="tlp-modal"
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="regenerate-gitlab-webhook">
                {{ $gettext("Regenerate the GitLab webhook") }}
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
            <p>
                {{
                    $gettext(
                        "Regenerate the GitLab webhook will invalidate the previous webhook, and create a new one with a new secret. Webhook is used to allow GitLab to securely communicate with Tuleap whenever something happen in the repository (e.g. push commits, new merge requests, ...).",
                    )
                }}
            </p>
            <p>{{ you_are_about_to_regenerate_the_webhook_for_repository_located_at_message }}</p>
            <p>{{ $gettext("Please confirm your action.") }}</p>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                data-test="regenerate-gitlab-webhook-cancel"
            >
                {{ $gettext("Cancel") }}
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
                {{ $gettext("Regenerate webhook") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { Repository } from "../../../type";
import { handleError } from "../../../gitlab/gitlab-error-handler";
import {
    useMutations,
    useNamespacedActions,
    useNamespacedMutations,
    useNamespacedState,
} from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const gettext_provider = useGettext();

const { regenerateGitlabWebhook } = useNamespacedActions("gitlab", ["regenerateGitlabWebhook"]);
const { regenerate_gitlab_webhook_repository } = useNamespacedState("gitlab", [
    "regenerate_gitlab_webhook_repository",
]);
const { setRegenerateGitlabWebhookModal } = useNamespacedMutations("gitlab", [
    "setRegenerateGitlabWebhookModal",
]);
const { setSuccessMessage } = useMutations(["setSuccessMessage"]);

const modal = ref<Modal | null>(null);
const repository = ref<Repository | null>(null);
const is_updating_webhook = ref(false);
const message_error_rest = ref("");

const modal_element = ref();

const close_label = gettext_provider.$gettext("Close");

onMounted((): void => {
    modal.value = createModal(modal_element.value);
    modal.value.addEventListener("tlp-modal-shown", onShownModal);
    modal.value.addEventListener("tlp-modal-hidden", reset);
    setRegenerateGitlabWebhookModal(modal.value);
});

function onShownModal(): void {
    repository.value = regenerate_gitlab_webhook_repository.value;
}

function reset(): void {
    is_updating_webhook.value = false;
    repository.value = null;
    message_error_rest.value = "";
}

const success_message = computed((): string => {
    if (!repository.value || !repository.value.normalized_path) {
        return "";
    }

    return gettext_provider.interpolate(
        gettext_provider.$gettext(
            "New webhook of GitLab repository %{ label } has been successfully regenerated.",
        ),
        {
            label: repository.value.normalized_path,
        },
    );
});

const have_any_rest_error = computed((): boolean => message_error_rest.value.length > 0);

const onSuccessRegenerateGitlabWebhook = (): void => setSuccessMessage(success_message.value);

const disabled_button = computed(() => is_updating_webhook.value || have_any_rest_error.value);

const instance_url = computed((): string => {
    if (!repository.value || !repository.value.gitlab_data || !repository.value.normalized_path) {
        return "";
    }
    return repository.value.gitlab_data.gitlab_repository_url.replace(
        repository.value.normalized_path,
        "",
    );
});

const confirmRegenerateWebhookGitlab = async (event: Event): Promise<void> => {
    event.preventDefault();

    if (have_any_rest_error.value) {
        return;
    }

    if (!repository.value) {
        return;
    }

    if (!repository.value.gitlab_data) {
        return;
    }

    is_updating_webhook.value = true;

    try {
        await regenerateGitlabWebhook(repository.value.integration_id);
        repository.value.gitlab_data.is_webhook_configured = true;
        onSuccessRegenerateGitlabWebhook();

        modal.value?.hide();
    } catch (rest_error) {
        message_error_rest.value = await handleError(rest_error, gettext_provider);
        throw rest_error;
    } finally {
        is_updating_webhook.value = false;
    }
};

const you_are_about_to_regenerate_the_webhook_for_repository_located_at_message = computed(() => {
    let translated = gettext_provider.$gettext(
        `You are about to regenerate the webhook for %{ label } repository located at %{ instance_url }.`,
    );
    return gettext_provider.interpolate(translated, {
        label: repository.value?.normalized_path,
        instance_url: instance_url.value,
    });
});

defineExpose({ message_error_rest, repository, is_updating_webhook, disabled_button });
</script>
