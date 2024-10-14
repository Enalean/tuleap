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
    <div
        v-bind:class="{
            'git-repository-list-folder': !props.is_root_folder,
            'git-repository-list-base-folder': props.is_base_folder,
        }"
        data-test="git-repository-list"
    >
        <div
            data-test="git-repository-list-folder-collapse"
            class="git-repository-list-collapsible-folder"
            v-on:click="collapseFolder()"
        >
            <i
                data-test="git-repository-list-folder-icon"
                v-if="!props.is_root_folder"
                v-bind:class="{
                    'fa fa-fw fa-caret-down': !is_folder_collapsed,
                    'fa fa-fw fa-caret-right': is_folder_collapsed,
                }"
            ></i>
            <h2
                class="git-repository-list-folder-label"
                v-if="!props.is_root_folder"
                data-test="git-repository-list-folder-label"
            >
                <i class="far fa-folder"></i>
                {{ label }}
            </h2>
        </div>
        <template v-for="child in props.children">
            <git-repository
                v-if="!('is_folder' in child)"
                v-show="!is_folder_collapsed"
                v-bind:key="child.id"
                v-bind:repository="child"
            />
            <collapsible-folder
                v-else
                v-show="!is_folder_collapsed"
                v-bind:key="child.label"
                v-bind:label="child.label"
                v-bind:children="child.children"
                v-bind:is_base_folder="props.is_root_folder"
                v-bind:is_root_folder="false"
                data-test="git-repository-collapsible-folder"
            />
        </template>
    </div>
</template>

<script setup lang="ts">
import GitRepository from "../GitRepository.vue";
import type { Folder, FormattedGitLabRepository, Repository } from "../../type";
import { ref } from "vue";

const props = defineProps<{
    label: string;
    is_root_folder: boolean;
    is_base_folder: boolean;
    children: Array<Folder | Repository | FormattedGitLabRepository>;
}>();

let is_folder_collapsed = ref(false);

function collapseFolder(): void {
    is_folder_collapsed.value = !is_folder_collapsed.value;
}
</script>
<script lang="ts">
export default {
    name: "CollapsibleFolder",
    inheritAttrs: false,
};
</script>
