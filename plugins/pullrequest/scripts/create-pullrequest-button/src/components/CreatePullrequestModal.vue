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
    <div class="tlp-modal" role="dialog">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title">{{ $gettext("Create a pull request") }}</h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-feedback" v-if="displayParentRepositoryWarning">
            <div class="tlp-alert-warning">
                {{ $gettext("You don't have permission to see parent repository's branches.") }}
            </div>
        </div>
        <div class="tlp-modal-body">
            <div class="tlp-alert-danger" v-if="create_error_message">
                {{ create_error_message }}
            </div>

            <div class="git-repository-actions-pullrequest-modal-body">
                <div class="tlp-form-element git-repository-actions-pullrequest-modal-body-element">
                    <label
                        class="tlp-label"
                        for="git-repository-actions-pullrequest-modal-body-source"
                        >{{ $gettext("Source branch") }}<i class="fa-solid fa-asterisk"></i></label
                    ><select
                        class="tlp-select"
                        id="git-repository-actions-pullrequest-modal-body-source"
                        data-test="pull-request-source-branch"
                        required
                        v-model="source_branch"
                    >
                        <option value="" selected disabled>
                            {{ $gettext("Choose source branch…") }}
                        </option>
                        <option
                            v-for="branch of source_branches"
                            v-bind:value="branch"
                            v-bind:key="branch.display_name"
                        >
                            {{ branch.display_name }}
                        </option>
                    </select>
                </div>
                <div class="tlp-form-element git-repository-actions-pullrequest-modal-body-element">
                    <label
                        class="tlp-label"
                        for="git-repository-actions-pullrequest-modal-body-destination"
                        >{{ $gettext("Destination branch")
                        }}<i class="fa-solid fa-asterisk"></i></label
                    ><select
                        class="tlp-select"
                        id="git-repository-actions-pullrequest-modal-body-destination"
                        data-test="pull-request-destination-branch"
                        required
                        v-model="destination_branch"
                    >
                        <option value="" selected disabled>
                            {{ $gettext("Choose destination branch") }}
                        </option>
                        <option
                            v-for="branch of destination_branches"
                            v-bind:value="branch"
                            v-bind:key="branch.display_name"
                        >
                            {{ branch.display_name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
        <div class="tlp-modal-footer tlp-modal-footer-large">
            <button
                type="submit"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="create_pullrequest()"
                v-bind:disabled="is_button_disabled"
                data-test="pull-request-create-button"
            >
                <i v-bind:class="is_creating_pullrequest_icon_class"></i
                >{{ $gettext("Create the pull request") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { ExtendedBranch } from "../helpers/pullrequest-helper.ts";
import {
    SOURCE_BRANCHES,
    DESTINATION_BRANCHES,
    SELECTED_SOURCE_BRANCH,
    SELECTED_DESTINATION_BRANCH,
    CREATE_ERROR_MESSAGE,
    IS_CREATING_PULLREQUEST,
    CREATE_PULLREQUEST,
} from "../injection-keys";
import { strictInject } from "@tuleap/vue-strict-inject";

defineProps<{
    displayParentRepositoryWarning: boolean;
}>();

const { $gettext } = useGettext();

const source_branches = strictInject(SOURCE_BRANCHES);
const destination_branches = strictInject(DESTINATION_BRANCHES);
const selected_source_branch = strictInject(SELECTED_SOURCE_BRANCH);
const selected_destination_branch = strictInject(SELECTED_DESTINATION_BRANCH);
const create_error_message = strictInject(CREATE_ERROR_MESSAGE);
const is_creating_pullrequest = strictInject(IS_CREATING_PULLREQUEST);
const create_pullrequest = strictInject(CREATE_PULLREQUEST);

const source_branch = computed({
    get(): ExtendedBranch | "" {
        return selected_source_branch.value ?? "";
    },
    set(value: ExtendedBranch | "") {
        selected_source_branch.value = value;
        create_error_message.value = "";
    },
});

const destination_branch = computed({
    get(): ExtendedBranch | "" {
        return selected_destination_branch.value ?? "";
    },
    set(value: ExtendedBranch | "") {
        selected_destination_branch.value = value;
        create_error_message.value = "";
    },
});

const is_button_disabled = computed(
    () =>
        is_creating_pullrequest.value ||
        !source_branch.value ||
        !destination_branch.value ||
        source_branch.value === destination_branch.value,
);

const is_creating_pullrequest_icon_class = computed(() => {
    if (!is_creating_pullrequest.value) {
        return "fa-solid fa-code-branch fa-rotate-270 tlp-button-icon";
    }
    return "fa-solid fa-circle-notch fa-spin tlp-button-icon";
});
</script>
