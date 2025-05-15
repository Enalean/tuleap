<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->

<template>
    <div class="tlp-pagination">
        <template v-if="from <= 0">
            <span
                class="tlp-button-primary tlp-button-outline tlp-pagination-button disabled"
                data-test="begin-disabled"
                v-bind:title="begin_title"
            >
                <i class="fa-solid fa-angle-double-left" aria-hidden="true"></i>
            </span>
            <span
                class="tlp-button-primary tlp-button-outline tlp-pagination-button disabled"
                data-test="previous-disabled"
                v-bind:title="previous_title"
            >
                <i class="fa-solid fa-angle-left" aria-hidden="true"></i>
            </span>
        </template>
        <template v-else>
            <router-link
                v-bind:to="begin_to"
                class="tlp-button-primary tlp-button-outline tlp-pagination-button"
                role="button"
                data-test="begin"
                v-bind:title="begin_title"
            >
                <i class="fa-solid fa-angle-double-left" aria-hidden="true"></i>
            </router-link>
            <router-link
                v-bind:to="to_previous"
                class="tlp-button-primary tlp-button-outline tlp-pagination-button"
                role="button"
                data-test="previous"
                v-bind:title="previous_title"
            >
                <i class="fa-solid fa-angle-left" aria-hidden="true"></i>
            </router-link>
        </template>

        <span class="tlp-pagination-pages" v-dompurify-html="pages" data-test="pages"></span>

        <template v-if="to >= total - 1">
            <span
                class="tlp-button-primary tlp-button-outline tlp-pagination-button disabled"
                data-test="next-disabled"
                v-bind:title="next_title"
            >
                <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
            </span>
            <span
                class="tlp-button-primary tlp-button-outline tlp-pagination-button disabled"
                data-test="end-disabled"
                v-bind:title="end_title"
            >
                <i class="fa-solid fa-angle-double-right" aria-hidden="true"></i>
            </span>
        </template>
        <template v-else>
            <router-link
                v-bind:to="to_next"
                class="tlp-button-primary tlp-button-outline tlp-pagination-button"
                role="button"
                data-test="next"
                v-bind:title="next_title"
            >
                <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
            </router-link>
            <router-link
                v-bind:to="to_end"
                class="tlp-button-primary tlp-button-outline tlp-pagination-button"
                role="button"
                data-test="end"
                v-bind:title="end_title"
            >
                <i class="fa-solid fa-angle-double-right" aria-hidden="true"></i>
            </router-link>
        </template>
    </div>
</template>
<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { computed } from "vue";
import type { RouteLocationNormalized } from "vue-router";
import { useRoute } from "vue-router";
import type { Dictionary } from "vue-router/types/router";

const props = defineProps<{ from: number; to: number; total: number; limit: number }>();

const { $gettext, interpolate } = useGettext();

const begin_title = $gettext("Begin");
const previous_title = $gettext("Previous");
const next_title = $gettext("Next");
const end_title = $gettext("End");

const route = useRoute();

const pages = computed((): string => {
    return interpolate(
        $gettext("%{ from } – %{ to } of %{ total }").replace(
            /(%\{\s*(?:from|to|total)\s*\})/g,
            '<span class="tlp-pagination-number">$1</span>',
        ),
        {
            from: props.from + 1,
            to: props.to + 1,
            total: props.total,
        },
    );
});

const begin_to = computed((): RouteLocationNormalized => {
    const query = getInitialQueryWithoutItsOffset();

    return {
        ...route,
        query,
    };
});

const to_previous = computed((): RouteLocationNormalized => {
    const query = getInitialQueryWithoutItsOffset();

    const new_offset = Math.max(0, props.from - props.limit);
    if (new_offset === 0) {
        return {
            ...route,
            query,
        };
    }

    return {
        ...route,
        query: {
            ...query,
            offset: String(new_offset),
        },
    };
});

const to_next = computed((): RouteLocationNormalized => {
    const new_offset = Math.min(props.total - 1, props.from + props.limit);

    return {
        ...route,
        query: {
            ...route.query,
            offset: String(new_offset),
        },
    };
});

const to_end = computed((): RouteLocationNormalized => {
    return {
        ...route,
        query: {
            ...route.query,
            offset: String(props.total - (props.total % props.limit)),
        },
    };
});

function getInitialQueryWithoutItsOffset(): Dictionary<string | (string | null)[]> {
    // We don't want to use offset from rest destructuring
    // See https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Destructuring_assignment#rest_in_object_destructuring
    //eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { offset, ...query } = route.query;

    return query;
}

defineExpose({
    pages,
    begin_to,
    to_previous,
    to_next,
});
</script>
