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
        <div class="tlp-dropdown-menu-actions" v-if="!is_loading_branches && branches.length">
            <refs-filter
                v-bind:placeholder="$gettext('Branch name')"
                v-on:update-filter="updateFilter"
            />
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
            >
                {{ $gettext("default") }}
            </span>
        </a>
        <div class="tlp-dropdown-menu-item" v-if="has_error_while_loading_branches">
            <div class="tlp-alert-danger">
                {{ $gettext("An error occurred while loading branches") }}
            </div>
        </div>
        <div
            class="git-repository-branch-tag-selector-empty"
            v-if="are_branches_loaded && !branches.length && !has_error_while_loading_branches"
        >
            {{ $gettext("There isn't any branches defined yet") }}
        </div>
        <div
            class="git-repository-branch-tag-selector-empty"
            v-if="are_branches_loaded && branches.length && !filtered_branches.length"
        >
            {{ $gettext("There isn't any matching branches") }}
        </div>
    </section>
</template>
<script setup lang="ts">
import { recursiveGet } from "@tuleap/tlp-fetch";
import encodeData from "../helpers/encode-data";
import RefsFilter from "./RefsFilter.vue";
import type { Ref } from "vue";
import { onMounted, computed, ref } from "vue";
import type { Branch, URLParameter } from "../type";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{
    repository_id: number;
    repository_url: string;
    repository_default_branch: string;
    is_displaying_branches: boolean;
    is_tag: boolean;
    current_ref_name: string;
    url_parameters: URLParameter;
}>();

const is_loading_branches = ref(true);
const are_branches_loaded = ref(false);
const has_error_while_loading_branches = ref(false);
const branches: Ref<Branch[]> = ref([]);
const filter_text = ref("");

const filter = computed((): string => {
    return filter_text.value.toLowerCase();
});

const filtered_branches = computed((): Branch[] => {
    return branches.value.filter(
        (branch) => branch.name.toLowerCase().indexOf(filter.value) !== -1,
    );
});

onMounted(() => {
    loadBranches();
});

async function loadBranches(): Promise<void> {
    try {
        branches.value = await recursiveGet(
            `/api/git/${encodeURIComponent(props.repository_id)}/branches`,
            {
                params: {
                    limit: 50,
                },
            },
        );
        branches.value.sort((branch_a, branch_b) => branch_a.name.localeCompare(branch_b.name));
    } catch (_e) {
        has_error_while_loading_branches.value = true;
    } finally {
        is_loading_branches.value = false;
        are_branches_loaded.value = true;
    }
}

function url(ref: string): string {
    return props.repository_url + "?" + encodeData({ ...props.url_parameters, hb: ref });
}

function updateFilter(value: string): void {
    filter_text.value = value;
}
</script>
