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
    <a
        v-bind:href="'/plugins/tracker/?aid=' + encodeURIComponent(backlog_item.artifact.id)"
        class="tlp-card tlp-card-selectable test-plan-backlog-item-card"
        v-bind:class="classname"
        v-on:click.prevent.stop="toggle"
    >
        <div>
            <i
                class="fa fa-fw test-plan-backlog-item-caret"
                v-bind:class="caret"
                aria-hidden="true"
            ></i>
            <span class="tlp-badge-outline" v-bind:class="badge_color">
                {{ backlog_item.short_type }} #{{ backlog_item.id }}
            </span>
            <span class="test-plan-backlog-item-title">
                {{ backlog_item.label }}
            </span>
        </div>
        <backlog-item-coverage v-bind:backlog_item="backlog_item" />
    </a>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { BacklogItem } from "../../type";
import { namespace } from "vuex-class";
import BacklogItemCoverage from "./BacklogItemCoverage.vue";

const backlog_item_store = namespace("backlog_item");
@Component({
    components: { BacklogItemCoverage },
})
export default class BacklogItemCard extends Vue {
    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    @backlog_item_store.Mutation
    readonly expandBacklogItem!: (item: BacklogItem) => void;

    @backlog_item_store.Mutation
    readonly collapseBacklogItem!: (item: BacklogItem) => void;

    toggle(): void {
        if (this.backlog_item.is_expanded) {
            this.collapseBacklogItem(this.backlog_item);
        } else {
            this.expandBacklogItem(this.backlog_item);
        }
    }

    get badge_color(): string {
        return "tlp-badge-" + this.backlog_item.color;
    }

    get caret(): string {
        if (this.backlog_item.is_expanded) {
            return "fa-caret-down";
        }

        return "fa-caret-right";
    }

    get classname(): string {
        if (this.backlog_item.is_expanded) {
            return "test-plan-backlog-item-card-expanded";
        }

        return "";
    }
}
</script>
