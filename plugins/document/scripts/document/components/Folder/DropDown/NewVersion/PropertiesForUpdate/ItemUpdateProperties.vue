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

<!-- eslint-disable vue/no-mutating-props -->
<template>
    <div class="docman-item-update-property">
        <version-title-property v-model="version.title" data-test="update-property-version-title" />
        <lock-property
            v-if="!isOpenAfterDnd"
            v-model="version.is_file_locked"
            v-bind:item="item"
            data-test="update-property-lock-version"
        />
        <changelog-property v-model="version.changelog" data-test="update-property-changelog" />
        <slot></slot>
        <approval-update-properties
            v-if="item.has_approval_table"
            v-on:approval-table-action-change="emitApprovalUpdateAction"
            data-test="update-approval-properties"
        />
    </div>
</template>

<script>
import VersionTitleProperty from "../../PropertiesForCreateOrUpdate/VersionTitleProperty.vue";
import ChangelogProperty from "../../PropertiesForCreateOrUpdate/ChangelogProperty.vue";
import LockProperty from "../../Lock/LockProperty.vue";
import ApprovalUpdateProperties from "./ApprovalUpdateProperties.vue";

export default {
    name: "ItemUpdateProperties",
    components: { LockProperty, ChangelogProperty, VersionTitleProperty, ApprovalUpdateProperties },
    props: {
        version: Object,
        item: Object,
        isOpenAfterDnd: {
            type: Boolean,
            default: false,
        },
    },
    methods: {
        emitApprovalUpdateAction(action) {
            this.$emit("approval-table-action-change", action);
        },
    },
};
</script>
