<!--
  - Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
    <section class="git-repository-branch-tag-selector-refs" v-if="!is_displaying_branches">
        <div class="git-repository-branch-tag-selector-is-loading" v-if="is_loading_tags"></div>
        <div class="tlp-dropdown-menu-actions" v-if="!is_loading_tags && tags.length">
            <refs-filter
                v-bind:placeholder="$gettext('Tag name')"
                v-on:update-filter="updateFilter"
            />
        </div>
        <a
            v-for="tag in filtered_tags"
            v-bind:key="tag.commit.id + tag.name"
            v-bind:href="url(tag.name)"
            class="tlp-dropdown-menu-item"
            role="menuitem"
        >
            <i
                class="fa fa-check fa-fw tlp-dropdown-menu-item-icon"
                v-if="is_tag && tag.name === current_ref_name"
            ></i>
            {{ tag.name }}
        </a>
        <div class="tlp-dropdown-menu-item" v-if="has_error_while_loading_tags">
            <div class="tlp-alert-danger">
                {{ $gettext("An error occurred while loading tags") }}
            </div>
        </div>
        <div
            class="git-repository-branch-tag-selector-empty"
            v-if="are_tags_loaded && !tags.length && !has_error_while_loading_tags"
            key="no-tags"
        >
            {{ $gettext("There isn't any tags defined yet") }}
        </div>
        <div
            class="git-repository-branch-tag-selector-empty"
            v-if="are_tags_loaded && tags.length && !filtered_tags.length"
            key="no-matching-tags"
        >
            {{ $gettext("There isn't any matching tags") }}
        </div>
    </section>
</template>
<script setup lang="ts">
import { recursiveGet } from "@tuleap/tlp-fetch";
import encodeData from "../helpers/encode-data";
import RefsFilter from "./RefsFilter.vue";
import type { Ref } from "vue";
import { computed, ref, watch } from "vue";
import type { Tag, URLParameter } from "../type";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{
    repository_id: number;
    repository_url: string;
    is_displaying_branches: boolean;
    is_tag: boolean;
    current_ref_name: string;
    url_parameters: URLParameter;
}>();

const is_loading_tags = ref(true);
const are_tags_loaded = ref(false);
const has_error_while_loading_tags = ref(false);
const tags: Ref<Tag[]> = ref([]);
const filter_text = ref("");

const filter = computed((): string => {
    return filter_text.value.toLowerCase();
});

const filtered_tags = computed((): Tag[] => {
    return tags.value.filter((tag) => tag.name.toLowerCase().indexOf(filter.value) !== -1);
});

watch(
    () => props.is_displaying_branches,
    async (): Promise<void> => {
        if (!props.is_displaying_branches && !are_tags_loaded.value) {
            await loadTags();
        }
    },
);

async function loadTags(): Promise<void> {
    try {
        tags.value = await recursiveGet(
            `/api/git/${encodeURIComponent(props.repository_id)}/tags`,
            {
                params: {
                    limit: 50,
                },
            },
        );
    } catch (_e) {
        has_error_while_loading_tags.value = true;
    } finally {
        is_loading_tags.value = false;
        are_tags_loaded.value = true;
    }
}

function url(ref: string): string {
    return props.repository_url + "?" + encodeData({ ...props.url_parameters, hb: ref });
}

function updateFilter(value: string): void {
    filter_text.value = value;
}
</script>
