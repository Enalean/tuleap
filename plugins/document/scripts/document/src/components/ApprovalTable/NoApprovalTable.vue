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
    <p>{{ $gettext("There is no approval table for this document.") }}</p>
    <a
        v-if="item.user_can_write"
        role="button"
        class="tlp-button-primary"
        v-bind:href="getLinkToApprovalTableCreation(item)"
        data-test="creation-button"
    >
        {{ $gettext("Create a new one") }}
    </a>
</template>

<script setup lang="ts">
import type { Item } from "../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT } from "../../configuration-keys";

defineProps<{ item: Item }>();

const project = strictInject(PROJECT);

function getLinkToApprovalTableCreation(item: Item): string {
    return `/plugins/docman/?group_id=${project.id}&action=approval_create&id=${item.id}`;
}
</script>
