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
    <tbody>
        <no-content-empty-state/>
    </tbody>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import NoContentSvg from "./NoContentSvg.vue";
import NoContentEmptyState from "./NoContentEmptyState.vue";
@Component({
    components: { NoContentEmptyState, NoContentSvg }
})
export default class TaskBoardBody extends Vue {}
</script>
