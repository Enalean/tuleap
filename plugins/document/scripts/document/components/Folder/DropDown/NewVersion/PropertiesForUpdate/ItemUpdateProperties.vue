<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="docman-item-update-property">
        <version-title-property
            v-bind:value="version.title"
            data-test="update-property-version-title"
        />
        <preview-filename-new-version
            v-bind:version="version"
            v-bind:item="item"
            v-if="isItemAFile"
        />
        <lock-property
            v-if="!isOpenAfterDnd"
            v-bind:item="item"
            data-test="update-property-lock-version"
        />
        <changelog-property
            v-bind:value="version.changelog"
            data-test="update-property-changelog"
        />
        <slot></slot>
        <approval-update-properties
            v-if="item.has_approval_table"
            v-on:approval-table-action-change="emitApprovalUpdateAction"
            data-test="update-approval-properties"
        />
    </div>
</template>

<script setup lang="ts">
import VersionTitleProperty from "../../PropertiesForCreateOrUpdate/VersionTitleProperty.vue";
import ChangelogProperty from "../../PropertiesForCreateOrUpdate/ChangelogProperty.vue";
import LockProperty from "../../Lock/LockProperty.vue";
import ApprovalUpdateProperties from "./ApprovalUpdateProperties.vue";
import PreviewFilenameNewVersion from "../PreviewFilenameNewVersion.vue";
import type { Item, NewVersion } from "../../../../../type";
import { isFile } from "../../../../../helpers/type-check-helper";

const props = defineProps<{ item: Item; isOpenAfterDnd: boolean; version: NewVersion }>();

const emit = defineEmits<{
    (e: "approval-table-action-change", action: string): void;
}>();

function emitApprovalUpdateAction(action: string) {
    emit("approval-table-action-change", action);
}

function isItemAFile(): boolean {
    return isFile(props.item);
}
</script>
