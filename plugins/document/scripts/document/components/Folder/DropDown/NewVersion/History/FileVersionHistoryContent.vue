<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -F
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
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <tr data-test="document-history-file">
        <td>
            <a v-bind:href="encodeURI(props.version.download_href)" data-test="download-version">{{
                props.version.number
            }}</a>
        </td>
        <td>
            {{ props.version.name }}
        </td>
        <td data-test="version-file-name">
            {{ props.version.filename }}
        </td>
    </tr>
</template>

<script setup lang="ts">
import type { FileHistory } from "../../../../../type";

const props = defineProps<{ version: FileHistory }>();
</script>
