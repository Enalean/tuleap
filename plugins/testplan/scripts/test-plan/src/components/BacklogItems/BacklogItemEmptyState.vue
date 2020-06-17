<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="empty-pane">
        <div class="empty-pane-illustration">
            <backlog-item-empty-state-svg />
        </div>
        <div class="empty-pane-text">
            <translate>There is no backlog item yet.</translate>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import BacklogItemEmptyStateSvg from "./BacklogItemEmptyStateSvg.vue";

@Component({
    components: { BacklogItemEmptyStateSvg },
})
export default class BacklogItemEmptyState extends Vue {}
</script>
