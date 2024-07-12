<!--
  - Copyright (c) Enalean, 2024-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-pagination">
        <a
            class="tlp-button-primary tlp-button-outline tlp-pagination-button"
            v-bind:class="{ disabled: offset <= 0 }"
            role="button"
            v-bind:title="$gettext('Begin')"
            v-on:click="firstPage"
            data-test="first-page-button"
        >
            <i class="fa-solid fa-angles-left" aria-hidden="true"></i>
        </a>
        <a
            class="tlp-button-primary tlp-button-outline tlp-pagination-button"
            v-bind:class="{ disabled: offset <= 0 }"
            role="button"
            v-bind:title="$gettext('Previous')"
            v-on:click="previousPage"
            data-test="previous-page-button"
        >
            <i class="fa-solid fa-angle-left" aria-hidden="true"></i>
        </a>

        <span class="tlp-pagination-pages">
            <span
                class="tlp-pagination-number"
                data-test="selectable-pagination-number-first-element"
                >{{ total_number > 0 ? offset + 1 : 0 }}
            </span>
            –
            <span class="tlp-pagination-number">{{ current_page_max_number }}</span>
            {{ $gettext("of") }}
            <span class="tlp-pagination-number">{{ total_number }}</span>
        </span>

        <a
            class="tlp-button-primary tlp-button-outline tlp-pagination-button"
            role="button"
            v-bind:title="$gettext('Next')"
            v-bind:class="{ disabled: offset + limit >= total_number }"
            v-on:click="nextPage"
            data-test="next-page-button"
        >
            <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
        </a>
        <a
            class="tlp-button-primary tlp-button-outline tlp-pagination-button"
            role="button"
            v-bind:title="$gettext('End')"
            v-bind:class="{ disabled: offset + limit >= total_number }"
            v-on:click="lastPage"
            data-test="last-page-button"
        >
            <i class="fa-solid fa-angles-right" aria-hidden="true"></i>
        </a>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    total_number: number;
    limit: number;
    offset: number;
}>();

const { $gettext } = useGettext();

const current_page_max_number = computed(() => {
    if (props.offset + props.limit >= props.total_number) {
        return props.total_number;
    }
    return props.limit + props.offset;
});

const emit = defineEmits<{
    (e: "new-page", new_offset: number): void;
}>();

function nextPage(): void {
    const new_offset = props.offset + props.limit;
    const is_new_page_offset_greater_than_total_number = new_offset > props.total_number;
    emit("new-page", is_new_page_offset_greater_than_total_number ? props.offset : new_offset);
}

function firstPage(): void {
    emit("new-page", 0);
}

function previousPage(): void {
    const new_offset = props.offset - props.limit;
    const is_new_page_offset_before_first_page = props.offset - props.limit <= 0;
    emit("new-page", is_new_page_offset_before_first_page ? 0 : new_offset);
}

function lastPage(): void {
    emit("new-page", props.total_number - props.limit);
}
</script>
