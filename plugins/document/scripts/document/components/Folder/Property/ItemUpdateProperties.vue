<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
        <div class="docman-item-title-update-property">
            <version-title-property v-model="version.title" />
            <lock-property v-model="version.is_file_locked" v-bind:item="item" />
        </div>
        <changelog-property v-model="version.changelog" />
        <slot></slot>
        <approval-update-properties
            v-if="item.has_approval_table"
            v-on:approvalTableActionChange="emitApprovalUpdateAction"
            data-test="update-approval-properties"
        />
    </div>
</template>

<script>
import VersionTitleProperty from "./VersionTitleProperty.vue";
import ChangelogProperty from "./ChangelogProperty.vue";
import LockProperty from "./LockProperty.vue";
import ApprovalUpdateProperties from "./ApprovalUpdateProperties.vue";

export default {
    name: "ItemUpdateProperties",
    components: { LockProperty, ChangelogProperty, VersionTitleProperty, ApprovalUpdateProperties },
    props: {
        version: Object,
        item: Object,
    },
    methods: {
        emitApprovalUpdateAction(action) {
            this.$emit("approvalTableActionChange", action);
        },
    },
};
</script>
