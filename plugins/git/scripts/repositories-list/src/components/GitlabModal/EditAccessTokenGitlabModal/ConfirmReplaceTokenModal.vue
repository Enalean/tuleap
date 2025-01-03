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
                <p>{{ $gettext("Please confirm your action.") }}</p>
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
                {{ $gettext("Back") }}
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
                {{ $gettext("Save new token") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Repository } from "../../../type";
import { computed, ref } from "vue";
import { handleError } from "../../../gitlab/gitlab-error-handler";
import { useNamespacedActions } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const gettext_provider = useGettext();

const { updateBotApiTokenGitlab } = useNamespacedActions("gitlab", ["updateBotApiTokenGitlab"]);

const props = defineProps<{
    repository: Repository;
    gitlab_new_token: string;
}>();

const emit = defineEmits<{
    (e: "on-back-button"): void;
    (e: "on-success-edit-token"): void;
}>();

const message_error_rest = ref("");
const is_patching_new_token = ref(false);

const instance_url = computed((): string => {
    if (!props.repository.gitlab_data || !props.repository.normalized_path) {
        return "";
    }
    return props.repository.gitlab_data.gitlab_repository_url.replace(
        props.repository.normalized_path,
        "",
    );
});

const confirmation_message = computed((): string => {
    return gettext_provider.interpolate(
        gettext_provider.$gettext(
            "You are about to update the token used to integrate %{ label } repository of %{ instance_url }.",
        ),
        {
            label: props.repository.normalized_path,
            instance_url: instance_url.value,
        },
    );
});

const have_any_rest_error = computed((): boolean => message_error_rest.value.length > 0);

const disabled_button = computed(
    (): boolean => is_patching_new_token.value || have_any_rest_error.value,
);

const reset = (): void => {
    is_patching_new_token.value = false;
    message_error_rest.value = "";
};

const onBackToEdit = (): void => {
    reset();
    emit("on-back-button");
};

const confirmEditToken = async (event: Event): Promise<void> => {
    event.preventDefault();

    if (have_any_rest_error.value) {
        return;
    }

    if (!props.repository.gitlab_data) {
        return;
    }

    is_patching_new_token.value = true;

    try {
        await updateBotApiTokenGitlab({
            gitlab_integration_id: props.repository.integration_id,
            gitlab_api_token: props.gitlab_new_token,
        });

        emit("on-success-edit-token");
    } catch (rest_error) {
        message_error_rest.value = await handleError(rest_error, gettext_provider);
        throw rest_error;
    } finally {
        is_patching_new_token.value = false;
    }
};

defineExpose({ message_error_rest, disabled_button });
</script>
