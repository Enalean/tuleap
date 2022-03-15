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
        v-bind:href="edit_backlog_item_href"
        class="tlp-card tlp-card-selectable test-plan-backlog-item-card"
        v-bind:class="{
            'test-plan-backlog-item-card-expanded': backlog_item.is_expanded,
            'test-plan-backlog-item-is-just-refreshed': backlog_item.is_just_refreshed,
        }"
        v-on:click.prevent.stop="toggle"
    >
        <div>
            <i
                class="fa fa-fw test-plan-backlog-item-caret"
                v-bind:class="caret"
                aria-hidden="true"
            ></i>
            <span class="cross-ref-badge" v-bind:class="badge_color">
                {{ backlog_item.short_type }} #{{ backlog_item.id }}
            </span>
            <span class="test-plan-backlog-item-title">
                {{ backlog_item.label }}
            </span>
            <add-test-button v-bind:backlog_item="backlog_item" />
        </div>
        <backlog-item-coverage v-bind:backlog_item="backlog_item" />
    </a>
</template>
<script setup lang="ts">
import BacklogItemCoverage from "./BacklogItemCoverage.vue";
import AddTestButton from "./AddTestButtonWithAdditionalActionsMenu.vue";
import type { BacklogItem } from "../../type";
import type { State } from "../../store/type";
import { useNamespacedMutations, useState } from "vuex-composition-helpers";
import type { BacklogItemMutations } from "../../store/backlog-item/backlog-item-mutations";
import { computed, onMounted } from "vue";
import { buildEditBacklogItemLink } from "../../helpers/BacklogItems/url-builder";

const { milestone_id } = useState<Pick<State, "milestone_id">>(["milestone_id"]);

const props = defineProps<{
    backlog_item: BacklogItem;
}>();

const { removeIsJustRefreshedFlagOnBacklogItem, expandBacklogItem, collapseBacklogItem } =
    useNamespacedMutations<
        Pick<
            BacklogItemMutations,
            "removeIsJustRefreshedFlagOnBacklogItem" | "expandBacklogItem" | "collapseBacklogItem"
        >
    >("backlog_item", [
        "removeIsJustRefreshedFlagOnBacklogItem",
        "expandBacklogItem",
        "collapseBacklogItem",
    ]);

onMounted((): void => {
    if (props.backlog_item.is_just_refreshed) {
        setTimeout(() => {
            removeIsJustRefreshedFlagOnBacklogItem(props.backlog_item);
        }, 1000);
    }
});

function toggle(): void {
    if (props.backlog_item.is_expanded) {
        collapseBacklogItem(props.backlog_item);
    } else {
        expandBacklogItem(props.backlog_item);
    }
}

const badge_color = computed((): string => {
    return "cross-ref-badge-" + props.backlog_item.color;
});

const caret = computed((): string => {
    if (props.backlog_item.is_expanded) {
        return "fa-caret-down";
    }

    return "fa-caret-right";
});

const edit_backlog_item_href = computed(() => {
    return buildEditBacklogItemLink(milestone_id.value, props.backlog_item);
});
</script>
<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({});
</script>
