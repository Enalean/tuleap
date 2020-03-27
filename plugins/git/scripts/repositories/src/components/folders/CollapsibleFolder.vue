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
    <div
        v-bind:class="{
            'git-repository-list-folder': !isRootFolder,
            'git-repository-list-base-folder': isBaseFolder,
        }"
    >
        <div class="git-repository-list-collapsible-folder" v-on:click="collapseFolder()">
            <i
                v-if="!isRootFolder"
                v-bind:class="{
                    'fa fa-fw fa-caret-down': !isFolderCollapsed,
                    'fa fa-fw fa-caret-right': isFolderCollapsed,
                }"
            ></i>
            <h2 class="git-repository-list-folder-label" v-if="!isRootFolder">
                <i class="fa fa-folder-o"></i>
                {{ label }}
            </h2>
        </div>
        <template v-for="child in children">
            <git-repository
                v-if="!child.is_folder"
                v-show="!isFolderCollapsed"
                v-bind:key="child.id"
                v-bind:repository="child"
            />
            <collapsible-folder
                v-else
                v-show="!isFolderCollapsed"
                v-bind:key="child.label"
                v-bind:label="child.label"
                v-bind:children="child.children"
                v-bind:is-base-folder="isRootFolder"
            />
        </template>
    </div>
</template>
<script>
import GitRepository from "../GitRepository.vue";

export default {
    name: "CollapsibleFolder",
    components: { GitRepository },
    props: {
        label: {
            type: String,
            required: false,
        },
        isRootFolder: {
            type: Boolean,
            required: false,
        },
        isBaseFolder: {
            type: Boolean,
            required: false,
        },
        children: Array,
    },
    data() {
        return { isFolderCollapsed: false };
    },
    methods: {
        collapseFolder() {
            this.isFolderCollapsed = !this.isFolderCollapsed;
        },
    },
};
</script>
