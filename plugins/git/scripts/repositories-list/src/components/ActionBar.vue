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
    <div class="git-repository-list-actions" v-if="is_first_load_done">
        <div v-if="showCreateRepositoryButton()">
            <dropdown-action-button v-if="areExternalUsedServices" v-bind:is_empty_state="false" />
            <button
                v-else
                type="button"
                class="tlp-button-primary git-repository-list-create-repository-button"
                v-on:click="showAddRepositoryModal()"
                data-test="create-repository-button"
            >
                <i class="fa fa-plus tlp-button-icon"></i>
                {{ $gettext("Create a repository") }}
            </button>
        </div>

        <select-owner />

        <div class="git-repository-list-actions-spacer" v-if="!isCurrentRepositoryListEmpty"></div>

        <template v-if="!isCurrentRepositoryListEmpty">
            <display-mode-switcher />
            <list-filter />
        </template>
    </div>
</template>
<script lang="ts">
import ListFilter from "./ActionBar/ListFilter.vue";
import SelectOwner from "./ActionBar/SelectOwner.vue";
import DisplayModeSwitcher from "./ActionBar/DisplayModeSwitcher.vue";
import DropdownActionButton from "./DropdownActionButton.vue";
import { getUserIsAdmin } from "../repository-list-presenter";
import { Component } from "vue-property-decorator";
import Vue from "vue";
import { Action, Getter, State } from "vuex-class";

@Component({ components: { DropdownActionButton, SelectOwner, ListFilter, DisplayModeSwitcher } })
export default class ActionBar extends Vue {
    @Getter
    readonly isCurrentRepositoryListEmpty!: boolean;
    @Getter
    readonly isInitialLoadingDoneWithoutError!: boolean;
    @Getter
    readonly areExternalUsedServices!: boolean;

    @State
    readonly is_first_load_done!: boolean | number;

    @Action
    readonly showAddRepositoryModal!: () => void;

    showCreateRepositoryButton(): boolean {
        return (
            getUserIsAdmin() &&
            !(this.isCurrentRepositoryListEmpty && this.isInitialLoadingDoneWithoutError)
        );
    }
}
</script>
