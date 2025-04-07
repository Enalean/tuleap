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
    <div class="tlp-dropdown-menu tlp-dropdown-with-tabs-on-top" role="menu" ref="dropdown_menu">
        <nav class="tlp-tabs git-repository-branch-tag-selector-tabs">
            <button
                type="button"
                role="menuitem"
                class="tlp-tab"
                v-bind:class="{ 'tlp-tab-active': is_displaying_branches }"
                v-on:click="is_displaying_branches = true"
            >
                {{ $gettext("Branches") }}
            </button>
            <button
                type="button"
                role="menuitem"
                class="tlp-tab"
                v-bind:class="{ 'tlp-tab-active': !is_displaying_branches }"
                v-on:click="is_displaying_branches = false"
            >
                {{ $gettext("Tags") }}
            </button>
        </nav>
        <branches-section
            v-bind:repository_id="repository_id"
            v-bind:repository_url="repository_url"
            v-bind:repository_default_branch="repository_default_branch"
            v-bind:is_displaying_branches="is_displaying_branches"
            v-bind:is_tag="is_tag"
            v-bind:current_ref_name="current_ref_name"
            v-bind:url_parameters="url_parameters"
        />
        <tags-section
            v-bind:repository_id="repository_id"
            v-bind:repository_url="repository_url"
            v-bind:is_displaying_branches="is_displaying_branches"
            v-bind:is_tag="is_tag"
            v-bind:current_ref_name="current_ref_name"
            v-bind:url_parameters="url_parameters"
        />
    </div>
</template>
<script setup lang="ts">
import { createDropdown } from "@tuleap/tlp-dropdown";
import BranchesSection from "./BranchesSection.vue";
import TagsSection from "./TagsSection.vue";
import { onMounted, ref } from "vue";
import type { URLParameter } from "../type";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    button: HTMLButtonElement;
    repository_id: number;
    repository_url: string;
    repository_default_branch: string;
    is_tag: boolean;
    current_ref_name: string;
    url_parameters: URLParameter;
}>();

const is_displaying_branches = ref(true);
const dropdown_menu = ref<HTMLDivElement>();

const { $gettext } = useGettext();

onMounted(() => {
    if (dropdown_menu.value instanceof HTMLDivElement) {
        const dropdown = createDropdown(props.button, {
            dropdown_menu: dropdown_menu.value,
        });
        dropdown.show();
    }
});
</script>
