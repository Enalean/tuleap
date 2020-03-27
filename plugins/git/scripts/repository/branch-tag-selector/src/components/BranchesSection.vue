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
    <section class="git-repository-branch-tag-selector-refs" v-if="is_displaying_branches">
        <div class="git-repository-branch-tag-selector-is-loading" v-if="is_loading_branches"></div>
        <div
            class="git-repository-branch-tag-selector-filter"
            v-if="!is_loading_branches && branches.length"
        >
            <refs-filter v-model="filter_text" v-bind:placeholder="placeholder" />
        </div>
        <a
            v-for="branch in filtered_branches"
            v-bind:key="branch.commit.id"
            v-bind:href="url(branch.name)"
            class="tlp-dropdown-menu-item"
            role="menuitem"
        >
            <i
                class="fa fa-check fa-fw tlp-dropdown-menu-item-icon"
                v-if="!is_tag && branch.name === current_ref_name"
            ></i>
            {{ branch.name }}
        </a>
        <div class="tlp-dropdown-menu-item" v-if="has_error_while_loading_branches">
            <div class="tlp-alert-danger" v-translate>
                An error occurred while loading branches
            </div>
        </div>
        <div
            class="git-repository-branch-tag-selector-empty"
            v-if="are_branches_loaded && !branches.length && !has_error_while_loading_branches"
            v-translate
        >
            There isn't any branches defined yet
        </div>
        <div
            class="git-repository-branch-tag-selector-empty"
            v-if="are_branches_loaded && branches.length && !filtered_branches.length"
            v-translate
        >
            There isn't any matching branches
        </div>
    </section>
</template>
<script>
import { recursiveGet } from "tlp";
import encodeData from "../helpers/encode-data.js";
import RefsFilter from "./RefsFilter.vue";

export default {
    name: "BranchesSection",
    components: { RefsFilter },
    props: {
        repository_id: Number,
        repository_url: String,
        is_displaying_branches: Boolean,
        is_tag: Boolean,
        current_ref_name: String,
        url_parameters: Object,
    },
    data() {
        return {
            is_loading_branches: true,
            are_branches_loaded: false,
            has_error_while_loading_branches: false,
            branches: [],
            filter_text: "",
        };
    },
    computed: {
        filtered_branches() {
            return this.branches.filter(
                (branch) => branch.name.toLowerCase().indexOf(this.filter) !== -1
            );
        },
        filter() {
            return this.filter_text.toLowerCase();
        },
        placeholder() {
            return this.$gettext("Branch name");
        },
    },
    mounted() {
        this.loadBranches();
    },
    methods: {
        async loadBranches() {
            try {
                this.branches = await recursiveGet(`/api/git/${this.repository_id}/branches`, {
                    params: {
                        limit: 50,
                    },
                });
                this.branches.sort((branch_a, branch_b) =>
                    branch_a.name.localeCompare(branch_b.name)
                );
            } catch (e) {
                this.has_error_while_loading_branches = true;
            } finally {
                this.is_loading_branches = false;
                this.are_branches_loaded = true;
            }
        },
        url(ref) {
            return this.repository_url + "?" + encodeData({ ...this.url_parameters, hb: ref });
        },
    },
};
</script>
