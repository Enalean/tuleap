<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <div class="document-header tlp-framed-horizontally">
        <document-title-lock-info v-bind:item="item" v-bind:is-displaying-in-header="true" />
        <h1 class="document-header-title">
            {{ item.title }}
        </h1>
    </div>
    <nav class="tlp-tabs">
        <router-link
            class="tlp-tab"
            v-bind:class="{ 'tlp-tab-active': active_tab === VersionsTab }"
            v-bind:to="{ name: 'versions', params: { item_id: item.id } }"
            v-if="item_has_versions"
            data-test="versions-link"
        >
            {{ $gettext("Versions") }}
        </router-link>
        <router-link
            class="tlp-tab"
            v-bind:class="{ 'tlp-tab-active': active_tab === LogsTab }"
            v-bind:to="{ name: 'history', params: { item_id: item.id } }"
        >
            {{ $gettext("Logs") }}
        </router-link>
        <router-link
            class="tlp-tab"
            v-bind:class="{ 'tlp-tab-active': active_tab === ReferencesTab }"
            v-bind:to="{ name: 'references', params: { item_id: item.id } }"
        >
            {{ $gettext("References") }}
        </router-link>
        <router-link
            class="tlp-tab"
            v-bind:class="{ 'tlp-tab-active': active_tab === NotificationsTab }"
            v-bind:to="{ name: 'notifications', params: { item_id: item.id } }"
        >
            {{ $gettext("Notifications") }}
        </router-link>
        <router-link
            v-if="item.type === TYPE_FOLDER"
            class="tlp-tab"
            v-bind:class="{ 'tlp-tab-active': active_tab === StatisticsTab }"
            v-bind:to="{ name: 'statistics', params: { item_id: item.id } }"
        >
            {{ $gettext("Statistics") }}
        </router-link>
        <router-link
            v-if="isAnApprovableDocument(item)"
            class="tlp-tab"
            v-bind:class="{ 'tlp-tab-active': active_tab === ApprovalTableTab }"
            v-bind:to="{ name: 'approval-table', params: { item_id: item.id } }"
        >
            {{ $gettext("Approval table") }}
        </router-link>
    </nav>
</template>
<script setup lang="ts">
import DocumentTitleLockInfo from "./LockInfo/DocumentTitleLockInfo.vue";
import type { Item } from "../../type";
import { computed } from "vue";
import { isEmbedded, isFile, isLink } from "../../helpers/type-check-helper";
import type { DetailsTabs } from "../../helpers/details-tabs";
import {
    ApprovalTableTab,
    LogsTab,
    NotificationsTab,
    ReferencesTab,
    StatisticsTab,
    VersionsTab,
} from "../../helpers/details-tabs";
import { TYPE_FOLDER } from "../../constants";
import { isAnApprovableDocument } from "../../helpers/approval-table-helper";

const props = defineProps<{
    item: Item;
    active_tab: DetailsTabs;
}>();
const item_has_versions = computed(
    (): boolean =>
        props.item !== null && (isFile(props.item) || isLink(props.item) || isEmbedded(props.item)),
);
defineExpose({ item_has_versions });
</script>
