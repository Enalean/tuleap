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
  -->

<template>
    <div class="taskboard-drop-not-accepted-overlay">
        <div class="taskboard-drop-not-accepted-overlay-content">
            <i class="fa fa-ban taskboard-drop-not-accepted-icon" aria-hidden="true"></i>
            <p data-test="overlay-error-message" v-if="!isColumnCollapsed" v-translate>
                This column does not allow to drop this card here.
            </p>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

@Component
export default class CardAssignees extends Vue {
    @Prop({ required: true })
    readonly isColumnCollapsed!: boolean;
}
</script>
