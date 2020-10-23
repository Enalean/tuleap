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
    <div class="git-repository-list-actions" v-if="is_first_load_done">
        <div v-if="show_create_repository_button">
            <dropdown-action-button v-if="areExternalUsedServices" />
            <button
                v-else
                type="button"
                class="tlp-button-primary git-repository-list-create-repository-button"
                v-on:click="showAddRepositoryModal()"
                data-test="create-repository-button"
            >
                <i class="fa fa-plus tlp-button-icon"></i>
                <translate>Create a repository</translate>
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
<script>
import { mapGetters, mapActions, mapState } from "vuex";
import ListFilter from "./ActionBar/ListFilter.vue";
import SelectOwner from "./ActionBar/SelectOwner.vue";
import DisplayModeSwitcher from "./ActionBar/DisplayModeSwitcher.vue";
import DropdownActionButton from "./DropdownActionButton.vue";
import { getUserIsAdmin } from "../repository-list-presenter.js";

export default {
    name: "ActionBar",
    components: {
        DropdownActionButton,
        SelectOwner,
        ListFilter,
        DisplayModeSwitcher,
    },
    computed: {
        show_create_repository_button() {
            return (
                getUserIsAdmin() &&
                !(this.isCurrentRepositoryListEmpty && this.isInitialLoadingDoneWithoutError)
            );
        },
        ...mapGetters([
            "isCurrentRepositoryListEmpty",
            "isInitialLoadingDoneWithoutError",
            "areExternalUsedServices",
        ]),
        ...mapState(["is_first_load_done"]),
    },
    methods: {
        ...mapActions(["showAddRepositoryModal"]),
    },
};
</script>
