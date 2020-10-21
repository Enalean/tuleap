<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="tlp-dropdown">
        <button
            class="tlp-button-primary"
            v-bind:class="{ 'tlp-button-large': is_empty_state }"
            ref="dropdownButton"
            type="button"
        >
            <translate>New repository</translate>
            <i class="fas fa-caret-down tlp-button-icon-right" aria-hidden="true"></i>
        </button>
        <div class="tlp-dropdown-menu" role="menu">
            <button
                type="button"
                class="tlp-dropdown-menu-item"
                v-on:click="showAddRepositoryModal()"
                data-test="create-repository-button"
            >
                <i class="fa fa-plus tlp-button-icon"></i>
                <translate class="git-add-action-button">Create a repository</translate>
            </button>
            <add-gitlab-project-action-button />
        </div>
    </div>
</template>

<script>
import { mapGetters, mapActions } from "vuex";
import { getUserIsAdmin } from "../repository-list-presenter.js";
import { createDropdown } from "tlp";
import AddGitlabProjectActionButton from "./AddGitlabProjectActionButton.vue";

export default {
    name: "DropdownActionButton",
    components: { AddGitlabProjectActionButton },
    props: {
        is_empty_state: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return { dropdown: null };
    },
    computed: {
        show_create_repository_button() {
            return (
                getUserIsAdmin() &&
                !(this.isCurrentRepositoryListEmpty && this.isInitialLoadingDoneWithoutError)
            );
        },
        ...mapGetters(["isCurrentRepositoryListEmpty", "isInitialLoadingDoneWithoutError"]),
    },
    mounted() {
        this.dropdown = createDropdown(this.$refs.dropdownButton);
    },
    methods: {
        ...mapActions(["showAddRepositoryModal"]),
    },
};
</script>
