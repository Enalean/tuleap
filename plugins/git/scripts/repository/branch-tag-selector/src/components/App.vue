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
    <div class="tlp-dropdown-menu tlp-dropdown-with-tabs-on-top" role="menu">
        <nav class="tlp-tabs git-repository-branch-tag-selector-tabs">
            <a
                href=""
                class="tlp-tab"
                v-bind:class="{ 'tlp-tab-active': is_displaying_branches }"
                v-on:click.prevent="is_displaying_branches = true"
                v-translate
            >
                Branches
            </a>
            <a
                href=""
                class="tlp-tab"
                v-bind:class="{ 'tlp-tab-active': !is_displaying_branches }"
                v-on:click.prevent="is_displaying_branches = false"
                v-translate
            >
                Tags
            </a>
        </nav>
        <branches-section
            v-bind:repository_id="repository_id"
            v-bind:repository_url="repository_url"
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
<script>
import { dropdown as createDropdown } from "tlp";
import BranchesSection from "./BranchesSection.vue";
import TagsSection from "./TagsSection.vue";

export default {
    name: "App",
    components: {
        BranchesSection,
        TagsSection,
    },
    props: {
        button: HTMLButtonElement,
        repository_id: Number,
        repository_url: String,
        is_tag: Boolean,
        current_ref_name: String,
        url_parameters: Object,
    },
    data() {
        return {
            is_displaying_branches: true,
        };
    },
    mounted() {
        const dropdown = createDropdown(this.button, {
            dropdown_menu: this.$el,
        });

        dropdown.show();
    },
};
</script>
