<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <document-layout>
        <template #document-content>
            <document-content v-bind:sections="mocked_sections">
                <template #section-header>
                    <h1>
                        <span class="tlp-skeleton-text"></span>
                    </h1>
                </template>
                <template #section-content>
                    <h1><span class="tlp-skeleton-text"></span></h1>
                    <p>
                        <span class="tlp-skeleton-text"></span>
                        <span class="tlp-skeleton-text"></span>
                        <span class="tlp-skeleton-text"></span>
                    </p>
                    <h2><span class="tlp-skeleton-text"></span></h2>
                    <p>
                        <span class="tlp-skeleton-text"></span>
                        <span class="tlp-skeleton-text"></span>
                    </p>
                </template>
            </document-content>
        </template>
        <template #table-of-contents>
            <table-of-contents v-bind:sections="mocked_sections">
                <span class="tlp-skeleton-text"></span>
            </table-of-contents>
        </template>
    </document-layout>
</template>

<script setup lang="ts">
import { ref } from "vue";
import DocumentContent from "@/components/DocumentContent.vue";
import TableOfContents from "@/components/TableOfContents.vue";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import DocumentLayout from "@/components/DocumentLayout.vue";

const mocked_sections = ref([
    ArtidocSectionFactory.create(),
    ArtidocSectionFactory.create(),
    ArtidocSectionFactory.create(),
]);
</script>
