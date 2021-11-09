<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div class="planned-iterations">
        <h2 class="planned-iterations-section-title" v-translate>Iterations</h2>
        <div class="empty-state-page" data-test="app-tmp-empty-state">
            <svg-planned-iterations-empty-state />
            <p class="empty-state-text planned-iterations-empty-state-text" v-translate>
                There is no iteration yet.
            </p>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import SvgPlannedIterationsEmptyState from "./SVGPlannedIterationsEmptyState.vue";

@Component({
    components: {
        SvgPlannedIterationsEmptyState,
    },
})
export default class PlannedIterationsSection extends Vue {}
</script>
