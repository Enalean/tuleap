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
    <div
        class="tlp-dropdown"
        v-bind:class="{ 'git-repository-list-create-repository-button': !is_empty_state }"
    >
        <button class="tlp-button-primary" ref="dropdownButton" type="button">
            {{ $gettext("New repository") }}
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
                <span class="git-add-action-button">{{ $gettext("Create a repository") }}</span>
            </button>
            <add-gitlab-repository-action-button />
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Action } from "vuex-class";
import { Component, Prop } from "vue-property-decorator";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import AddGitlabRepositoryActionButton from "./AddGitlabRepositoryActionButton.vue";

@Component({ components: { AddGitlabRepositoryActionButton } })
export default class DropdownActionButton extends Vue {
    @Prop({ required: true })
    readonly is_empty_state!: boolean;

    @Action
    readonly showAddRepositoryModal!: () => void;

    private dropdown: null | Dropdown = null;

    mounted(): void {
        if (!(this.$refs.dropdownButton instanceof Element)) {
            throw new Error("Can not find DOM element for dropdown");
        }
        this.dropdown = createDropdown(this.$refs.dropdownButton);
    }
}
</script>
