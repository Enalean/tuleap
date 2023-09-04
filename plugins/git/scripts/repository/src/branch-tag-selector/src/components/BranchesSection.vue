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
            v-bind:key="branch.commit.id + branch.name"
            v-bind:href="url(branch.name)"
            class="tlp-dropdown-menu-item"
            role="menuitem"
        >
            <i
                class="fa fa-check fa-fw tlp-dropdown-menu-item-icon"
                v-if="!is_tag && branch.name === current_ref_name"
            ></i>
            {{ branch.name }}
            <span
                class="tlp-badge-secondary tlp-badge-outline"
                v-if="branch.name === repository_default_branch"
                v-translate
            >
                default
            </span>
        </a>
        <div class="tlp-dropdown-menu-item" v-if="has_error_while_loading_branches">
            <div class="tlp-alert-danger" v-translate>An error occurred while loading branches</div>
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
<script lang="ts">
import { recursiveGet } from "@tuleap/tlp-fetch";
import encodeData from "../helpers/encode-data";
import RefsFilter from "./RefsFilter.vue";
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Branch, URLParameter } from "../type";

@Component({ components: { RefsFilter } })
export default class BranchesSection extends Vue {
    @Prop()
    readonly repository_id!: number;
    @Prop()
    readonly repository_url!: string;
    @Prop({ required: true })
    readonly repository_default_branch!: string;
    @Prop()
    readonly is_displaying_branches!: boolean;
    @Prop()
    readonly is_tag!: boolean;
    @Prop()
    readonly current_ref_name!: string;
    @Prop()
    readonly url_parameters!: URLParameter;

    is_loading_branches = true;
    are_branches_loaded = false;
    has_error_while_loading_branches = false;
    branches: Branch[] = [];
    filter_text = "";

    get filtered_branches(): Branch[] {
        return this.branches.filter(
            (branch) => branch.name.toLowerCase().indexOf(this.filter) !== -1,
        );
    }
    get filter(): string {
        return this.filter_text.toLowerCase();
    }
    get placeholder(): string {
        return this.$gettext("Branch name");
    }

    mounted(): void {
        this.loadBranches();
    }

    async loadBranches(): Promise<void> {
        try {
            this.branches = await recursiveGet(`/api/git/${this.repository_id}/branches`, {
                params: {
                    limit: 50,
                },
            });
            this.branches.sort((branch_a, branch_b) => branch_a.name.localeCompare(branch_b.name));
        } catch (e) {
            this.has_error_while_loading_branches = true;
        } finally {
            this.is_loading_branches = false;
            this.are_branches_loaded = true;
        }
    }

    url(ref: string): string {
        return this.repository_url + "?" + encodeData({ ...this.url_parameters, hb: ref });
    }
}
</script>
