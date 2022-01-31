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
                <i class="fas fa-angle-double-left" aria-hidden="true"></i>
            </span>
            <span
                class="tlp-button-primary tlp-button-outline tlp-pagination-button disabled"
                data-test="previous-disabled"
                v-bind:title="previous_title"
            >
                <i class="fas fa-angle-left" aria-hidden="true"></i>
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
                <i class="fas fa-angle-double-left" aria-hidden="true"></i>
            </router-link>
            <router-link
                v-bind:to="to_previous"
                class="tlp-button-primary tlp-button-outline tlp-pagination-button"
                role="button"
                data-test="previous"
                v-bind:title="previous_title"
            >
                <i class="fas fa-angle-left" aria-hidden="true"></i>
            </router-link>
        </template>

        <span class="tlp-pagination-pages" v-dompurify-html="pages" data-test="pages"></span>

        <template v-if="to >= total - 1">
            <span
                class="tlp-button-primary tlp-button-outline tlp-pagination-button disabled"
                data-test="next-disabled"
                v-bind:title="next_title"
            >
                <i class="fas fa-angle-right" aria-hidden="true"></i>
            </span>
            <span
                class="tlp-button-primary tlp-button-outline tlp-pagination-button disabled"
                data-test="end-disabled"
                v-bind:title="end_title"
            >
                <i class="fas fa-angle-double-right" aria-hidden="true"></i>
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
                <i class="fas fa-angle-right" aria-hidden="true"></i>
            </router-link>
            <router-link
                v-bind:to="to_end"
                class="tlp-button-primary tlp-button-outline tlp-pagination-button"
                role="button"
                data-test="end"
                v-bind:title="end_title"
            >
                <i class="fas fa-angle-double-right" aria-hidden="true"></i>
            </router-link>
        </template>
    </div>
</template>
<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Route } from "vue-router/types/router";
import type { Dictionary } from "vue-router/types/router";

@Component
export default class SearchResultPagination extends Vue {
    @Prop({ required: true })
    readonly from!: number;

    @Prop({ required: true })
    readonly to!: number;

    @Prop({ required: true })
    readonly total!: number;

    @Prop({ required: true })
    readonly limit!: number;

    get pages(): string {
        return this.$gettextInterpolate(
            this.$gettext("%{ from } – %{ to } of %{ total }").replace(
                /(%\{\s*(?:from|to|total)\s*\})/g,
                '<span class="tlp-pagination-number">$1</span>'
            ),
            {
                from: this.from + 1,
                to: this.to + 1,
                total: this.total,
            }
        );
    }

    get begin_title(): string {
        return this.$gettext("Begin");
    }

    get previous_title(): string {
        return this.$gettext("Previous");
    }

    get next_title(): string {
        return this.$gettext("Next");
    }

    get end_title(): string {
        return this.$gettext("End");
    }

    get begin_to(): Route {
        const query = this.getInitialQueryWithoutItsOffset();

        return {
            ...this.$route,
            query,
        };
    }

    get to_previous(): Route {
        const query = this.getInitialQueryWithoutItsOffset();

        const new_offset = Math.max(0, this.from - this.limit);
        if (new_offset === 0) {
            return {
                ...this.$route,
                query,
            };
        }

        return {
            ...this.$route,
            query: {
                ...query,
                offset: String(new_offset),
            },
        };
    }

    get to_next(): Route {
        const new_offset = Math.min(this.total - 1, this.from + this.limit);

        return {
            ...this.$route,
            query: {
                ...this.$route.query,
                offset: String(new_offset),
            },
        };
    }

    get to_end(): Route {
        return {
            ...this.$route,
            query: {
                ...this.$route.query,
                offset: String(this.total - (this.total % this.limit)),
            },
        };
    }

    getInitialQueryWithoutItsOffset(): Dictionary<string | (string | null)[]> {
        // We don't want to use offset from rest destructuring
        // See https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Destructuring_assignment#rest_in_object_destructuring
        //eslint-disable-next-line @typescript-eslint/no-unused-vars
        const { offset, ...query } = this.$route.query;

        return query;
    }
}
</script>
