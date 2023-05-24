<!--
  - Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
    <div class="labeled-items-list">
        <div
            v-if="loading"
            class="labeled-items-loading"
            v-bind:class="{ error: error !== false }"
        ></div>
        <div
            v-if="error !== false"
            class="tlp-alert-danger labeled-items-error"
            data-test="widget-error"
        >
            <translate>Please select one or more labels by editing this widget.</translate>
        </div>
        <div class="empty-state-pane" v-if="empty && !loading && error === false">
            <translate class="empty-state-text" v-if="are_there_items_user_cannot_see">
                There are no items you can see.
            </translate>
            <translate
                v-else
                v-bind:translate-n="labels_id.length"
                translate-plural="There isn't any item corresponding to labels."
                class="empty-state-text"
                data-test="items-list-empty-state"
            >
                There isn't any item corresponding to label.
            </translate>
        </div>
        <labeled-item v-for="item in items" v-bind:item="item" v-bind:key="item.html_url" />
        <div class="labeled-items-list-more" v-if="has_more_items" data-test="load-more-section">
            <button
                class="tlp-button-primary tlp-button-outline"
                v-on:click="loadMore"
                data-test="load-more-button"
            >
                <i class="tlp-button-icon fa fa-spinner fa-spin" v-if="is_loading_more"></i>
                <translate>Load more</translate>
            </button>
        </div>
    </div>
</template>
<script>
import LabeledItem from "./LabeledItem.vue";
import { getLabeledItems } from "./rest-querier.js";

export default {
    name: "LabeledItemsList",
    components: { LabeledItem },
    props: {
        labelsId: {
            type: String,
            default: "",
        },
        projectId: {
            type: String,
            default: "",
        },
    },
    data() {
        return {
            items: [],
            loading: true,
            error: false,
            are_there_items_user_cannot_see: false,
            offset: 0,
            limit: 50,
            has_more_items: false,
            is_loading_more: false,
        };
    },
    computed: {
        labels_id() {
            return JSON.parse(this.labelsId);
        },
        empty() {
            return this.items.length === 0;
        },
    },
    mounted() {
        this.loadLabeledItems();
    },
    methods: {
        async loadLabeledItems() {
            if (this.labels_id.length === 0) {
                this.error = true;
                this.loading = false;
                return;
            }

            try {
                const { labeled_items, are_there_items_user_cannot_see, has_more, offset } =
                    await getLabeledItems(this.projectId, this.labels_id, this.offset, this.limit);

                this.offset = offset;
                this.has_more_items = has_more;
                this.items = this.items.concat(labeled_items);

                this.are_there_items_user_cannot_see = are_there_items_user_cannot_see;
            } catch (e) {
                const { error } = await e.response.json();
                this.error = error.code + " " + error.message;
            } finally {
                this.loading = false;
            }
        },
        async loadMore() {
            this.is_loading_more = true;

            this.offset += this.limit;
            await this.loadLabeledItems();

            this.is_loading_more = false;
        },
    },
};
</script>
