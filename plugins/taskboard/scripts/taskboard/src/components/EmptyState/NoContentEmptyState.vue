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

<template functional>
    <div class="empty-page">
        <div class="empty-page-illustration">
            <svg xmlns="http://www.w3.org/2000/svg" width="534" height="170" fill="none">
                <use href="../../images/illustrations.svg#no-content-empty-state" class="tlp-illustration"/>
            </svg>
        </div>
        <translate tag="p" class="empty-page-text">
            This taskboard is empty
        </translate>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";

@Component
export default class NoContentEmptyState extends Vue {}
</script>
