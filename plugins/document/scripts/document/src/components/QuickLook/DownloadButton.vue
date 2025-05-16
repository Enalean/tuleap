<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <a v-bind:href="download_href" v-if="download_href !== ''">
        <i class="fa-solid fa-download tlp-button-icon" aria-hidden="true"></i>
        {{ $gettext("Download") }}
    </a>
</template>

<script setup lang="ts">
import type { ItemFile } from "../../type";
import { computed } from "vue";

const props = defineProps<{ item: ItemFile }>();

const download_href = computed((): string => props.item.file_properties?.download_href || "");
</script>
