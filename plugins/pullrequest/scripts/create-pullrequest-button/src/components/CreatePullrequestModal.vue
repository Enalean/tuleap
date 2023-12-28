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
            <h1 class="tlp-modal-title">{{ create_label }}</h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close_label"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-feedback" v-if="displayParentRepositoryWarning">
            <div class="tlp-alert-warning">{{ no_permission_message }}</div>
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
                        >{{ source_branch_label }}<i class="fa fa-asterisk"></i></label
                    ><select
                        class="tlp-select"
                        id="git-repository-actions-pullrequest-modal-body-source"
                        required
                        v-model="source_branch"
                    >
                        <option value="" selected disabled>{{ choose_source }}</option>
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
                        >{{ destination_branch_label }}<i class="fa fa-asterisk"></i></label
                    ><select
                        class="tlp-select"
                        id="git-repository-actions-pullrequest-modal-body-destination"
                        required
                        v-model="destination_branch"
                    >
                        <option value="" selected disabled>{{ choose_destination }}</option>
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
                {{ cancel_label }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="create()"
                v-bind:disabled="is_button_disabled"
            >
                <i v-bind:class="is_creating_pullrequest_icon_class"></i>{{ create_button }}
            </button>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";

export default {
    name: "CreatePullrequestModal",
    props: {
        displayParentRepositoryWarning: Boolean,
    },
    computed: {
        ...mapState([
            "source_branches",
            "destination_branches",
            "create_error_message",
            "is_creating_pullrequest",
        ]),
        is_button_disabled() {
            return (
                this.is_creating_pullrequest ||
                !this.source_branch ||
                !this.destination_branch ||
                this.source_branch === this.destination_branch
            );
        },
        is_creating_pullrequest_icon_class() {
            if (!this.is_creating_pullrequest) {
                return "fas fa-code-branch fa-rotate-270 tlp-button-icon";
            }

            return "fa fa-spinner fa-spin tlp-button-icon";
        },
        source_branch: {
            get() {
                return this.$store.state.selected_source_branch;
            },
            set(value) {
                this.$store.commit("setSelectedSourceBranch", value);
            },
        },
        destination_branch: {
            get() {
                return this.$store.state.selected_destination_branch;
            },
            set(value) {
                this.$store.commit("setSelectedDestinationBranch", value);
            },
        },
        create_label() {
            return this.$gettext("Create a pull request");
        },
        close_label() {
            return this.$gettext("Close");
        },
        no_permission_message() {
            return this.$gettext("You don't have permission to see parent repository's branches.");
        },
        source_branch_label() {
            return this.$gettext("Source branch");
        },
        choose_source() {
            return this.$gettext("Choose source branch…");
        },
        destination_branch_label() {
            return this.$gettext("Destination branch");
        },
        choose_destination() {
            return this.$gettext("Choose destination branch");
        },
        cancel_label() {
            return this.$gettext("Cancel");
        },
        create_button() {
            return this.$gettext("Create the pull request");
        },
    },
    methods: {
        create() {
            this.$store.dispatch("create", {
                source_branch: this.source_branch,
                destination_branch: this.destination_branch,
            });
        },
    },
};
</script>
