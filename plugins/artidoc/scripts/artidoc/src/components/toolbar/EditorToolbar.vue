<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
    <tuleap-prose-mirror-toolbar
        class="artidoc-toolbar"
        v-bind:controller="controller"
        v-bind:text_elements="{
            bold: true,
            italic: true,
            code: true,
            quote: true,
        }"
        v-bind:script_elements="{
            subscript: true,
            superscript: true,
        }"
        v-bind:link_elements="{ link: true, unlink: true, image: true }"
        v-bind:list_elements="{
            ordered_list: true,
            bullet_list: true,
        }"
    />
</template>

<script setup lang="ts">
import { TOOLBAR_BUS } from "@/toolbar-bus-injection-key";
import { strictInject } from "@tuleap/vue-strict-inject";
import { ToolbarController } from "@tuleap/prose-mirror-editor-toolbar";

const toolbar_bus = strictInject(TOOLBAR_BUS);
const controller = ToolbarController(toolbar_bus);
</script>

<style scoped lang="scss">
@use "@/themes/includes/zindex";
@use "@tuleap/burningparrot-theme/css/includes/global-variables";

.artidoc-toolbar {
    // Display block is mandatory to avoid flickering with the toolbar
    display: block;
    position: sticky;
    z-index: zindex.$toolbar;
    top: global-variables.$navbar-height;
}
</style>
