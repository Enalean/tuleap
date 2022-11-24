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
        <div
            class="git-repository-branch-tag-selector-filter"
            v-if="!is_loading_tags && tags.length"
        >
            <refs-filter v-model="filter_text" v-bind:placeholder="placeholder" />
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
            <div class="tlp-alert-danger" v-translate>An error occurred while loading tags</div>
        </div>
        <div
            class="git-repository-branch-tag-selector-empty"
            v-if="are_tags_loaded && !tags.length && !has_error_while_loading_tags"
            key="no-tags"
            v-translate
        >
            There isn't any tags defined yet
        </div>
        <div
            class="git-repository-branch-tag-selector-empty"
            v-if="are_tags_loaded && tags.length && !filtered_tags.length"
            key="no-matching-tags"
            v-translate
        >
            There isn't any matching tags
        </div>
    </section>
</template>
<script lang="ts">
import { recursiveGet } from "@tuleap/tlp-fetch";
import encodeData from "../helpers/encode-data";
import RefsFilter from "./RefsFilter.vue";
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import type { Tag, URLParameter } from "../type";

@Component({ components: { RefsFilter } })
export default class TagsSection extends Vue {
    @Prop()
    readonly repository_id!: number;
    @Prop()
    readonly repository_url!: string;
    @Prop()
    readonly is_displaying_branches!: boolean;
    @Prop()
    readonly is_tag!: boolean;
    @Prop()
    readonly current_ref_name!: string;
    @Prop()
    readonly url_parameters!: URLParameter;

    is_loading_tags = true;
    are_tags_loaded = false;
    has_error_while_loading_tags = false;
    tags: Tag[] = [];
    filter_text = "";

    get filtered_tags(): Tag[] {
        return this.tags.filter((tag) => tag.name.toLowerCase().indexOf(this.filter) !== -1);
    }

    get filter(): string {
        return this.filter_text.toLowerCase();
    }

    get placeholder(): string {
        return this.$gettext("Tag name");
    }

    @Watch("is_displaying_branches")
    async displaying_branches(is_displaying_branches: boolean): Promise<void> {
        if (!is_displaying_branches && !this.are_tags_loaded) {
            await this.loadTags();
        }
    }

    async loadTags(): Promise<void> {
        try {
            this.tags = await recursiveGet(`/api/git/${this.repository_id}/tags`, {
                params: {
                    limit: 50,
                },
            });
        } catch (e) {
            this.has_error_while_loading_tags = true;
        } finally {
            this.is_loading_tags = false;
            this.are_tags_loaded = true;
        }
    }

    url(ref: string): string {
        return this.repository_url + "?" + encodeData({ ...this.url_parameters, hb: ref });
    }
}
</script>
