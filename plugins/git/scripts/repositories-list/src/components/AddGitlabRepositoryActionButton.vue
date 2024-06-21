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
    <div v-if="isGitlabUsed" class="git-action-dropdown">
        <button
            type="button"
            data-test="gitlab-project-button"
            v-on:click="showModal"
            class="tlp-dropdown-menu-item"
            role="menuitem"
        >
            <i class="fab fa-gitlab tlp-button-icon git-gitlab-icon" aria-hidden="true"></i>
            <span class="git-add-action-button">{{ $gettext("Add GitLab repository") }}</span>
        </button>
    </div>
</template>

<script setup lang="ts">
import { useGetters, useNamespacedMutations } from "vuex-composition-helpers";

const { isGitlabUsed } = useGetters(["isGitlabUsed"]);
const { showAddGitlabRepositoryModal } = useNamespacedMutations("gitlab", [
    "showAddGitlabRepositoryModal",
]);

function showModal(): void {
    showAddGitlabRepositoryModal();
}
</script>
