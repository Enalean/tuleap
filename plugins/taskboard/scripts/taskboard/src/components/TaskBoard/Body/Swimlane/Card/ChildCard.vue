<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div
        class="taskboard-card taskboard-draggable-item"
        v-bind:class="additional_classnames"
        v-if="card.is_open || are_closed_items_displayed"
        v-bind:data-card-id="card.id"
    >
        <div class="taskboard-card-content">
            <card-xref-label v-bind:card="card"/>
            <div class="taskboard-card-info">
                <card-assignees v-bind:assignees="card.assignees"/>
            </div>
        </div>
        <div class="taskboard-card-accessibility" v-if="show_accessibility_pattern"></div>
    </div>
</template>

<script lang="ts">
import { Component, Mixins } from "vue-property-decorator";
import CardXrefLabel from "./CardXrefLabel.vue";
import CardAssignees from "./CardAssignees.vue";
import CardMixin from "./card-mixin";
import { State } from "vuex-class";

@Component({
    components: { CardXrefLabel, CardAssignees }
})
export default class ChildCard extends Mixins(CardMixin) {
    @State
    readonly are_closed_items_displayed!: boolean;
}
</script>
