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
    <div class="document-header">
        <h1 class="artidoc-header-title">
            {{ title }}
        </h1>
        <document-dropdown />
    </div>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { TITLE } from "@/title-injection-key";
import DocumentDropdown from "@/components/DocumentDropdown.vue";

const title = strictInject(TITLE);
</script>

<style scoped lang="scss">
@use "@/themes/includes/whitespace";

.document-header {
    display: flex;
    gap: var(--tlp-medium-spacing);
    padding: whitespace.$header-title-padding-top var(--tlp-medium-spacing)
        whitespace.$header-title-padding-bottom;
}

:fullscreen .document-header {
    display: none;
}

.artidoc-header-title {
    margin: 0;
}
</style>
