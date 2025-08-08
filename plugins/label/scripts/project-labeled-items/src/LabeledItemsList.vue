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
            {{ $gettext("Please select one or more labels by editing this widget.") }}
        </div>
        <div class="empty-state-pane" v-if="empty && !loading && error === false">
            <span class="empty-state-text" v-if="are_there_items_user_cannot_see">
                {{ $gettext("There are no items you can see.") }}
            </span>
            <span v-else class="empty-state-text" data-test="items-list-empty-state">
                {{
                    $ngettext(
                        "There isn't any item corresponding to label.",
                        "There isn't any item corresponding to labels.",
                        labels_id.length,
                    )
                }}
            </span>
        </div>
        <labeled-item v-for="item in items" v-bind:item="item" v-bind:key="item.html_url" />
        <div class="labeled-items-list-more" v-if="has_more_items" data-test="load-more-section">
            <button
                class="tlp-button-primary tlp-button-outline"
                v-on:click="loadMore"
                data-test="load-more-button"
            >
                <i class="tlp-button-icon fa fa-spinner fa-spin" v-if="is_loading_more"></i>
                {{ $gettext("Load more") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from "vue";

import LabeledItem from "./LabeledItem.vue";
import { getLabeledItems } from "./rest-querier.js";
import type { Item } from "./type";
const props = defineProps<{
    labelsId: string;
    projectId: string;
}>();

const items = ref<Array<Item>>([]);
const loading = ref(true);
const error = ref(false);
const are_there_items_user_cannot_see = ref(false);
const current_offset = ref(0);
const limit = ref(50);
const has_more_items = ref(false);
const is_loading_more = ref(false);

const labels_id = computed(() => {
    return JSON.parse(props.labelsId);
});

const empty = computed((): boolean => {
    return items.value.length === 0;
});

onMounted(() => {
    loadLabeledItems();
});

async function loadLabeledItems(): Promise<void> {
    if (labels_id.value.length === 0) {
        error.value = true;
        loading.value = false;
        return;
    }

    try {
        const { labeled_items, are_there_items_user_cannot_see_response, has_more, offset } =
            await getLabeledItems(
                props.projectId,
                labels_id.value,
                current_offset.value,
                limit.value,
            );

        current_offset.value = offset;
        has_more_items.value = has_more;
        items.value = items.value.concat(labeled_items);

        are_there_items_user_cannot_see.value = are_there_items_user_cannot_see_response;
    } catch (e) {
        error.value = true;
    } finally {
        loading.value = false;
    }
}

async function loadMore(): Promise<void> {
    is_loading_more.value = true;

    current_offset.value += limit.value;
    await loadLabeledItems();

    is_loading_more.value = false;
}
</script>
