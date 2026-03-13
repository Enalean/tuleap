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
    <div class="tlp-property">
        <label class="tlp-label">{{ label }}</label>
        <a
            v-bind:href="table.version_open_href"
            v-if="table.version_id !== null"
            data-test="version-link"
        >
            {{ table.version_label || table.version_number }}
        </a>
        <template v-else>
            <p data-test="version-not-found">{{ $gettext("Version not found") }}</p>
        </template>
    </div>
</template>

<script setup lang="ts">
import type { ApprovalTable } from "../../type";

defineProps<{
    table: Pick<
        ApprovalTable,
        "version_id" | "version_open_href" | "version_label" | "version_number"
    >;
    label: string;
}>();
</script>
