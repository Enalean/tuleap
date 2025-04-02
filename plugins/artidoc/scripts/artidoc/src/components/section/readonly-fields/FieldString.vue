<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <label class="tlp-label document-label">{{ field_string.label }}</label>
    <p v-if="field_string.value">{{ field_string.value }}</p>
    <p v-else class="tlp-property-empty">{{ $gettext("Empty") }}</p>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { ReadonlyFieldString } from "@/sections/readonly-fields/ReadonlyFieldsCollection";

const gettext_provider = useGettext();
const { $gettext } = gettext_provider;
defineProps<{
    field_string: ReadonlyFieldString;
}>();
</script>
