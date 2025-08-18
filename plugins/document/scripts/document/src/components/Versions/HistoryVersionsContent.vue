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
    <tbody v-if="isEmbedded(item)">
        <history-versions-content-row-for-embedded-file
            v-for="version in versions"
            v-bind:key="version.id"
            v-bind:item="item"
            v-bind:version="embeddedFileVersion(version)"
            v-bind:has_more_than_one_version="versions.length > 1"
            v-bind:load-versions="loadVersions"
        />
    </tbody>
    <tbody v-else>
        <history-versions-content-row
            v-for="version in versions"
            v-bind:key="version.id"
            v-bind:item="item"
            v-bind:version="fileHistory(version)"
            v-bind:has_more_than_one_version="versions.length > 1"
            v-bind:load-versions="loadVersions"
        />
    </tbody>
</template>

<script setup lang="ts">
import type { Embedded, EmbeddedFileVersion, FileHistory, ItemFile } from "../../type";
import HistoryVersionsContentRow from "./HistoryVersionsContentRow.vue";
import { isEmbedded } from "../../helpers/type-check-helper";
import HistoryVersionsContentRowForEmbeddedFile from "./HistoryVersionsContentRowForEmbeddedFile.vue";

defineProps<{
    item: ItemFile | Embedded;
    versions: ReadonlyArray<FileHistory | EmbeddedFileVersion>;
    loadVersions: () => void;
}>();

function isEmbeddedVersion(item: FileHistory | EmbeddedFileVersion): item is EmbeddedFileVersion {
    return "open_href" in item;
}

function embeddedFileVersion(item: FileHistory | EmbeddedFileVersion): EmbeddedFileVersion {
    if (isEmbeddedVersion(item)) {
        return item;
    }

    throw Error("Expected an EmbeddedFileVersion but got a FileHistory");
}

function fileHistory(item: FileHistory | EmbeddedFileVersion): FileHistory {
    if (!isEmbeddedVersion(item)) {
        return item;
    }

    throw Error("Expected a FileHistory but got an EmbeddedFileVersion");
}
</script>
