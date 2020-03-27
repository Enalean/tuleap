<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div>
        <div class="tlp-form-element">
            <label class="tlp-label tlp-checkbox">
                <input
                    type="checkbox"
                    data-test="delete-associated-wiki-page-checkbox"
                    v-on:click="processInput"
                />
                <span v-translate>Propagate deletion to wiki service</span>
            </label>
            <p class="tlp-text-warning">
                <translate>
                    Please note that if you check this option, the referenced wiki page will no
                    longer exist in the wiki service too.
                </translate>
            </p>
        </div>
        <div
            class="tlp-alert-warning"
            v-if="is_option_checked && wikiPageReferencers.length > 0"
            data-test="delete-associated-wiki-page-warning-message"
        >
            <p>{{ wiki_deletion_warning }}</p>
            <ul>
                <li v-for="referencer in wikiPageReferencers" v-bind:key="referencer.id">
                    <a
                        v-bind:href="getWikiPageUrl(referencer)"
                        class="wiki-page-referencer-link"
                        data-test="wiki-page-referencer-link"
                    >
                        {{ referencer.path }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";
import { sprintf } from "sprintf-js";

export default {
    props: {
        item: Object,
        model: Object,
        wikiPageReferencers: Array,
    },
    data() {
        return {
            is_option_checked: false,
        };
    },
    computed: {
        ...mapState(["project_id"]),
        wiki_deletion_warning() {
            return sprintf(
                this.$gettext(
                    'You should also be aware that the following wiki documents referencing page "%s" will no longer be valid if you choose to propagate the deletion to the wiki service:'
                ),
                this.item.wiki_properties.page_name
            );
        },
    },
    methods: {
        processInput($event) {
            const is_checked = $event.target.checked;

            this.$emit("input", { delete_associated_wiki_page: is_checked });

            this.is_option_checked = is_checked;
        },
        getWikiPageUrl(referencer) {
            return `/plugins/docman/?group_id=${encodeURIComponent(
                this.project_id
            )}&action=show&id=${encodeURIComponent(referencer.id)}`;
        },
    },
};
</script>
