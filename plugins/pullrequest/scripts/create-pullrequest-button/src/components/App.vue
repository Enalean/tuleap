<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div>
        <create-pullrequest-button v-bind:show-modal="showModal" />
        <create-pullrequest-modal
            v-bind:display-parent-repository-warning="display_parent_repository_warning"
            ref="modal_ref"
        />
        <create-pullrequest-error-modal ref="error_modal_ref" />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, provide, ref } from "vue";
import type { ComponentPublicInstance } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import CreatePullrequestButton from "./CreatePullrequestButton.vue";
import CreatePullrequestModal from "./CreatePullrequestModal.vue";
import CreatePullrequestErrorModal from "./CreatePullrequestErrorModal.vue";
import { buildPullrequestState } from "../helpers/pullrequest-state";
import {
    SOURCE_BRANCHES,
    DESTINATION_BRANCHES,
    SELECTED_SOURCE_BRANCH,
    SELECTED_DESTINATION_BRANCH,
    CREATE_ERROR_MESSAGE,
    HAS_ERROR_WHILE_LOADING_BRANCHES,
    IS_CREATING_PULLREQUEST,
    CAN_CREATE_PULLREQUEST,
    INIT_PULLREQUEST_DATA,
    CREATE_PULLREQUEST,
    RESET_SELECTION,
} from "../injection-keys";

const props = defineProps<{
    repository_id: number;
    project_id: number;
    parent_repository_id: number;
    parent_repository_name: string;
    parent_project_id: number;
    user_can_see_parent_repository: boolean;
}>();

const pullrequest_state = buildPullrequestState();

provide(SOURCE_BRANCHES, pullrequest_state.source_branches);
provide(DESTINATION_BRANCHES, pullrequest_state.destination_branches);
provide(SELECTED_SOURCE_BRANCH, pullrequest_state.selected_source_branch);
provide(SELECTED_DESTINATION_BRANCH, pullrequest_state.selected_destination_branch);
provide(CREATE_ERROR_MESSAGE, pullrequest_state.create_error_message);
provide(HAS_ERROR_WHILE_LOADING_BRANCHES, pullrequest_state.has_error_while_loading_branches);
provide(IS_CREATING_PULLREQUEST, pullrequest_state.is_creating_pullrequest);
provide(CAN_CREATE_PULLREQUEST, pullrequest_state.can_create_pullrequest);
provide(INIT_PULLREQUEST_DATA, pullrequest_state.init);
provide(CREATE_PULLREQUEST, pullrequest_state.create);
provide(RESET_SELECTION, pullrequest_state.resetSelection);

const display_parent_repository_warning = computed(
    () => !Number.isNaN(props.parent_repository_id) && !props.user_can_see_parent_repository,
);

const modal_ref = ref<ComponentPublicInstance | null>(null);
const error_modal_ref = ref<ComponentPublicInstance | null>(null);

let modal: Modal | null = null;
let error_modal: Modal | null = null;

onMounted(() => {
    pullrequest_state.init({
        repository_id: props.repository_id,
        project_id: props.project_id,
        parent_repository_id: props.parent_repository_id,
        parent_repository_name: props.parent_repository_name,
        parent_project_id: props.parent_project_id,
        user_can_see_parent_repository: props.user_can_see_parent_repository,
    });

    if (modal_ref.value) {
        modal = createModal(modal_ref.value.$el);
        modal.addEventListener("tlp-modal-hidden", resetModal);
    }

    if (error_modal_ref.value) {
        error_modal = createModal(error_modal_ref.value.$el);
    }
});

function showModal(): void {
    if (pullrequest_state.has_error_while_loading_branches.value) {
        error_modal?.toggle();
    } else {
        modal?.toggle();
    }
}

function resetModal(): void {
    pullrequest_state.resetSelection();
}
</script>
