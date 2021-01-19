<!---
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
    <div class="element-card" v-bind:class="`element-card-${element.tracker.color_name}`">
        <div class="element-card-content">
            <div class="element-card-xref-label">
                <a
                    v-bind:href="`/plugins/tracker/?aid=${element.artifact_id}`"
                    class="element-card-xref"
                    v-bind:class="`element-card-xref-${element.tracker.color_name}`"
                >
                    {{ element.tracker.label }} #{{ element.artifact_id }}
                </a>
                <span class="element-card-label">{{ element.artifact_title }}</span>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import EmptyState from "./EmptyState.vue";
import { ToBePlannedElement } from "../../../helpers/ToBePlanned/element-to-plan-retriever";

@Component({
    components: { EmptyState },
})
export default class ToBePlannedCard extends Vue {
    @Prop({ required: true })
    readonly element!: ToBePlannedElement;
}
</script>
