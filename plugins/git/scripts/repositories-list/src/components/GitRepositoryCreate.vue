<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    <form
        role="dialog"
        aria-labelledby="create-repository-modal-title"
        id="create-repository-modal"
        class="tlp-modal"
        v-on:submit="createRepository"
        ref="modal_element"
        data-test="create-repository-modal-form"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="create-repository-modal-title">
                {{ $gettext("Add project repository") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body git-repository-create-modal-body">
            <div
                v-if="error.length > 0"
                class="tlp-alert-danger"
                data-test="git-repository-create-modal-body-error"
            >
                {{ error }}
            </div>

            <div class="tlp-form-element">
                <label class="tlp-label" for="repository_name">
                    {{ $gettext("Repository name") }}
                    <i class="fa fa-asterisk"></i>
                </label>
                <input
                    type="text"
                    class="tlp-input"
                    id="repository_name"
                    required
                    v-model="repository_name"
                    v-bind:placeholder="$gettext('Repository name')"
                    pattern="[a-zA-Z0-9\/_.\-]{1,255}"
                    maxlength="255"
                    v-bind:title="$gettext('Allowed characters: a-zA-Z0-9/_.-')"
                    data-test="create_repository_name"
                />
                <p class="tlp-text-info">
                    <i class="fa fa-info-circle"></i>
                    {{
                        $gettext(
                            'Allowed characters: a-zA-Z0-9/_.- and max length is 255, no slashes at the beginning or the end, and repositories names must not finish with ".git".',
                        )
                    }}
                </p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_loading"
                data-test="create_repository"
            >
                <i
                    class="fa fa-plus tlp-button-icon"
                    v-bind:class="{ 'fa-spin fa-spinner': is_loading }"
                ></i>
                {{ $gettext("Add project repository") }}
            </button>
        </div>
    </form>
</template>
<script setup lang="ts">
import { onMounted, ref } from "vue";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { createModal } from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import { getProjectId } from "../repository-list-presenter";
import { postRepository } from "../api/rest-querier";
import { useMutations } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const { setAddRepositoryModal } = useMutations(["setAddRepositoryModal"]);

const modal = ref<Modal | null>(null);
const error = ref("");
const is_loading = ref(false);
const repository_name = ref("");

const modal_element = ref();

const reset = (): void => {
    repository_name.value = "";
    error.value = "";
};

onMounted(() => {
    modal.value = createModal(modal_element.value);

    modal.value.addEventListener("tlp-modal-hidden", reset);

    setAddRepositoryModal(modal.value);
});

async function createRepository(event: Event): Promise<void> {
    event.preventDefault();
    is_loading.value = true;
    error.value = "";
    try {
        const repository = await postRepository(getProjectId(), repository_name.value);
        window.location.href = repository.html_url;
    } catch (e) {
        let error_code: number | undefined = undefined;
        if (e instanceof FetchWrapperError) {
            const { error } = await e.response.json();
            error_code = Number.parseInt(error.code, 10);
        }
        if (error_code === 400) {
            error.value = $gettext(
                'Repository name is not well formatted or is already used. Allowed characters: a-zA-Z0-9/_.- and max length is 255, no slashes at the beginning or the end, and repositories names must not finish with ".git".',
            );
        } else if (error_code === 401) {
            error.value = $gettext(
                "You don't have permission to create Git repositories as you are not Git administrator.",
            );
        } else if (error_code === 404) {
            error.value = $gettext("Project not found");
        } else {
            error.value = $gettext("An error occurred while creating the repository.");
        }
        is_loading.value = false;
    }
}
</script>
