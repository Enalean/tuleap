<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
        id="pull-request-update-reviewers-modal"
        class="tlp-modal"
        role="dialog"
        aria-labelledby="update-reviewers-modal-title"
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 id="update-reviewers-modal-title" class="tlp-modal-title">
                {{ $gettext("Manage reviewers") }}
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
        <div class="tlp-modal-body">
            <div class="tlp-form-element">
                <label class="tlp-label" for="update-reviewers-modal-select">{{
                    $gettext("Reviewers")
                }}</label>
                <tuleap-lazybox id="update-reviewers-modal-select" ref="reviewer_input" />
            </div>
        </div>
        <div class="tlp-modal-footer pull-request-manage-reviewers-modal-footer">
            <template v-if="has_no_user_selected">
                <p
                    class="tlp-text-info pull-request-manage-reviewers-modal-footer-info"
                    data-test="text-info-all-reviewers-cleared"
                >
                    {{ $gettext("This pull request will be cleared from all of its reviewers.") }}
                </p>
                <div class="tlp-modal-footer-expander"></div>
            </template>
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-bind:disabled="is_saving"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                data-test="save-reviewers-button"
                v-bind:disabled="is_saving"
                v-on:click="saveReviewers"
            >
                {{ $gettext("Save changes") }}
                <i
                    v-if="is_saving"
                    class="tlp-button-icon-right fa-solid fa-circle-notch fa-spin"
                ></i>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import "@tuleap/lazybox";
import type { Lazybox } from "@tuleap/lazybox";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { DISPLAY_TULEAP_API_ERROR, PULL_REQUEST_ID_KEY } from "../../constants";
import { putReviewers } from "../../api/tuleap-rest-querier";
import { UsersToLazyboxItemsTransformer } from "./autocomplete/UsersToLazyboxItemsTransformer";
import { GroupOfReviewersBuilder } from "./autocomplete/GroupOfReviewersBuilder";
import { ReviewersAutocompleter } from "./autocomplete/ReviewersAutocompleter";
import {
    getAssignableReviewerTemplate,
    getSelectedReviewers,
} from "./autocomplete/AssignableReviewerTemplate";

const { $gettext } = useGettext();

const pull_request_id = strictInject(PULL_REQUEST_ID_KEY);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);

const props = defineProps<{
    readonly reviewers_list: ReadonlyArray<User>;
    readonly on_save_callback: (reviewers: ReadonlyArray<User>) => void;
    readonly on_cancel_callback: () => void;
}>();

const modal_element = ref<Element | undefined>();
const modal_instance = ref<Modal | null>(null);
const is_saving = ref(false);
const reviewer_input = ref<Lazybox | undefined>();
const currently_selected_users = ref<Array<User>>([]);
const has_no_user_selected = computed(
    () => props.reviewers_list.length > 0 && currently_selected_users.value.length === 0,
);

const users_transformer = UsersToLazyboxItemsTransformer();
const group_builder = GroupOfReviewersBuilder(users_transformer, $gettext);
const autocompleter = ReviewersAutocompleter(group_builder);

const cancel = () => {
    props.on_cancel_callback();
};

onMounted((): void => {
    if (!modal_element.value || !reviewer_input.value) {
        return;
    }

    modal_instance.value = createModal(modal_element.value, {
        destroy_on_hide: false,
        keyboard: false,
    });

    modal_instance.value.show();
    modal_instance.value.addEventListener(EVENT_TLP_MODAL_HIDDEN, cancel);

    initReviewersAutocompleter(reviewer_input.value);
});

onBeforeUnmount(() => {
    if (modal_instance.value) {
        modal_instance.value.removeEventListener(EVENT_TLP_MODAL_HIDDEN, cancel);
    }
});

function initReviewersAutocompleter(lazybox: Lazybox): void {
    lazybox.options = {
        is_multiple: true,
        placeholder: $gettext("Search users by their names"),
        templating_callback: getAssignableReviewerTemplate,
        search_input_callback: (query) => {
            autocompleter.autocomplete(lazybox, currently_selected_users.value, query);
        },
        selection_callback: (selected_users) => {
            currently_selected_users.value = getSelectedReviewers(selected_users);
        },
    };
    lazybox.replaceDropdownContent([group_builder.buildEmpty()]);
    lazybox.replaceSelection(users_transformer.buildForSelection(props.reviewers_list));
}

function saveReviewers(): void {
    is_saving.value = true;

    putReviewers(pull_request_id, currently_selected_users.value)
        .match(() => {
            if (modal_instance.value) {
                modal_instance.value.hide();
            }

            props.on_save_callback(currently_selected_users.value);
        }, displayTuleapAPIFault)
        .finally(() => {
            is_saving.value = false;
        });
}
</script>

<style lang="scss">
.pull-request-reviewers-badge {
    display: flex;
    gap: var(--tlp-small-spacing);
    align-items: center;
}

.pull-request-manage-reviewers-modal-footer {
    align-items: center;

    > .pull-request-manage-reviewers-modal-footer-info {
        flex: 1 1 auto;
        margin: 0;
        font-size: 14px;
    }
}
</style>
