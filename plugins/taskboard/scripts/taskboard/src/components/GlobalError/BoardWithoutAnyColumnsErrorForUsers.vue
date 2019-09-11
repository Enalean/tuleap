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
            <svg xmlns="http://www.w3.org/2000/svg" width="364" height="189" fill="none">
                <use href="../../images/illustrations.svg#board-without-any-columns-for-user" class="tlp-illustration"/>
            </svg>
        </div>
        <div class="empty-page-text-with-small-text">
            <translate>This taskboard is not ready to be used</translate>
            <div class="empty-page-small-text">
                <translate>Your project administrator needs to finish the planning configuration</translate>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";

@Component
export default class BoardWithoutAnyColumnsErrorForUsers extends Vue {}
</script>
