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
    <section class="git-repository-branch-tag-selector-refs" v-if="! is_displaying_branches">
        <div class="git-repository-branch-tag-selector-is-loading" v-if="is_loading_tags"></div>
        <a v-for="tag in tags"
           v-bind:key="tag.commit.id"
           v-bind:href="repository_url + '?a=tree&hb=' + tag.name"
           class="tlp-dropdown-menu-item"
           role="menuitem"
        >
            <i class="fa fa-check fa-fw tlp-dropdown-menu-item-icon" v-if="is_tag && tag.name === current_ref_name"></i>
            {{ tag.name }}
        </a>
        <div class="tlp-dropdown-menu-item" v-if="has_error_while_loading_tags">
            <div class="tlp-alert-danger" translate>
                An error occurred while loading tags
            </div>
        </div>
        <div class="git-repository-branch-tag-selector-empty"
             v-if="are_tags_loaded && ! tags.length && !has_error_while_loading_tags"
             translate
        >
            There isn't any tags defined yet
        </div>
    </section>
</template>
<script>
import { recursiveGet } from "tlp";

export default {
    name: "TagsSection",
    props: {
        repository_id: Number,
        repository_url: String,
        is_displaying_branches: Boolean,
        is_tag: Boolean,
        current_ref_name: String
    },
    data() {
        return {
            is_loading_tags: true,
            are_tags_loaded: false,
            has_error_while_loading_tags: false,
            tags: []
        };
    },
    methods: {
        async loadTags() {
            try {
                this.tags = await recursiveGet(`/api/git/${this.repository_id}/tags`, {
                    params: {
                        limit: 50
                    }
                });
            } catch (e) {
                this.has_error_while_loading_tags = true;
            } finally {
                this.is_loading_tags = false;
                this.are_tags_loaded = true;
            }
        }
    },
    watch: {
        is_displaying_branches(is_displaying_branches) {
            if (!is_displaying_branches && !this.are_tags_loaded) {
                this.loadTags();
            }
        }
    }
};
</script>
