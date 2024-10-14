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
    <section class="empty-state-page" v-if="show_empty_state()">
        <div class="empty-state-text">
            <h1 class="empty-state-title" data-test="no-repositories">
                {{ $gettext("There are no repositories in this project") }}
            </h1>
            <div v-if="is_admin()" class="empty-state-action">
                <dropdown-action-button
                    v-if="areExternalUsedServices"
                    v-bind:is_empty_state="true"
                />
                <button
                    type="button"
                    class="tlp-button-primary"
                    v-else
                    v-on:click="showAddRepositoryModal()"
                    data-test="create-repository-button"
                >
                    <i class="fa fa-plus tlp-button-icon"></i>
                    {{ $gettext("Add project repository") }}
                </button>
            </div>
        </div>
    </section>
</template>
<script setup lang="ts">
import { getUserIsAdmin } from "../repository-list-presenter";
import DropdownActionButton from "./DropdownActionButton.vue";
import { useActions, useGetters } from "vuex-composition-helpers";

const { showAddRepositoryModal } = useActions(["showAddRepositoryModal"]);

const { isCurrentRepositoryListEmpty, isInitialLoadingDoneWithoutError, areExternalUsedServices } =
    useGetters([
        "isCurrentRepositoryListEmpty",
        "isInitialLoadingDoneWithoutError",
        "areExternalUsedServices",
    ]);

function is_admin(): boolean {
    return getUserIsAdmin();
}

function show_empty_state(): boolean {
    return isCurrentRepositoryListEmpty.value && isInitialLoadingDoneWithoutError.value;
}
</script>
