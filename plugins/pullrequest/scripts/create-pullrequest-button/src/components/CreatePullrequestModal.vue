<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
            <h1 class="tlp-modal-title">
                <i class="fa fa-code-fork fa-rotate-270 tlp-modal-title-icon"></i>
                <translate>Create a pull request</translate>
            </h1>
            <div class="tlp-modal-close" data-dismiss="modal" aria-label="Close">
                &times;
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
                    >
                        <translate>Source branch</translate>
                        <i class="fa fa-asterisk"></i>
                    </label>
                    <select
                        class="tlp-select"
                        id="git-repository-actions-pullrequest-modal-body-source"
                        required
                        v-model="source_branch"
                    >
                        <option value="" selected disabled>Choose source branch…</option>
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
                    >
                        <translate>Destination branch</translate>
                        <i class="fa fa-asterisk"></i>
                    </label>
                    <select
                        class="tlp-select"
                        id="git-repository-actions-pullrequest-modal-body-destination"
                        required
                        v-model="destination_branch"
                    >
                        <option value="" selected disabled>Choose destination branch</option>
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
                <translate>Cancel</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="create()"
                v-bind:disabled="is_button_disabled"
            >
                <i v-bind:class="is_creating_pullrequest_icon_class"></i>
                <translate>Create the pull request</translate>
            </button>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";

export default {
    name: "CreatePullrequestModal",
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
                return "fa fa-code-fork fa-rotate-270 tlp-button-icon";
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
