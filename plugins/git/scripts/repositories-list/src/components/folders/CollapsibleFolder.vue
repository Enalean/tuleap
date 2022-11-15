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
            'git-repository-list-folder': !is_root_folder,
            'git-repository-list-base-folder': is_base_folder,
        }"
    >
        <div
            data-test="git-repository-list-folder-collapse"
            class="git-repository-list-collapsible-folder"
            v-on:click="collapseFolder()"
        >
            <i
                data-test="git-repository-list-folder-icon"
                v-if="!is_root_folder"
                v-bind:class="{
                    'fa fa-fw fa-caret-down': !is_folder_collapsed,
                    'fa fa-fw fa-caret-right': is_folder_collapsed,
                }"
            ></i>
            <h2
                class="git-repository-list-folder-label"
                v-if="!is_root_folder"
                data-test="git-repository-list-folder-label"
            >
                <i class="far fa-folder"></i>
                {{ label }}
            </h2>
        </div>
        <template v-for="child in children">
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
                v-bind:is_base_folder="is_root_folder"
                data-test="git-repository-collapsible-folder"
            />
        </template>
    </div>
</template>

<script lang="ts">
import GitRepository from "../GitRepository.vue";
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import type { Folder, Repository } from "../../type";

// The name attributes is needed because this component is recursive
// See https://github.com/kaorun343/vue-property-decorator/issues/102
@Component({ name: "CollapsibleFolder", components: { GitRepository, CollapsibleFolder } })
export default class CollapsibleFolder extends Vue {
    @Prop({ required: false, default: undefined })
    readonly label: undefined | string;
    @Prop({ required: false, default: false })
    readonly is_root_folder!: boolean;
    @Prop({ required: false, default: false })
    readonly is_base_folder!: boolean;
    @Prop({ required: true })
    readonly children!: Array<Folder | Repository>;

    is_folder_collapsed = false;

    collapseFolder(): void {
        this.is_folder_collapsed = !this.is_folder_collapsed;
    }
}
</script>
