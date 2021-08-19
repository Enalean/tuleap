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
  -
  -->

<template>
    <section class="empty-state-page">
        <div class="empty-state-illustration">
            <no-content-empty-state-illustration />
        </div>
        <h1 class="empty-state-title" v-translate>This taskboard is empty</h1>
    </section>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
@Component({
    components: {
        "no-content-empty-state-illustration": (): Promise<Record<string, unknown>> =>
            import(
                /* webpackChunkName: "taskboard-no-content-empty-state-illustration" */ "./NoContentEmptyStateIllustration.vue"
            ),
    },
})
export default class NoContentEmptyState extends Vue {}
</script>
