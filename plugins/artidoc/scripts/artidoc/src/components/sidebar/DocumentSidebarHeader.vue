<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <template v-if="are_versions_displayed">
        <nav class="tlp-tabs">
            <button class="tlp-tab tlp-tab-active" type="button">
                {{ $gettext("Table of contents") }}
            </button>
            <button class="tlp-tab" type="button" disabled>
                {{ $gettext("Versions") }}
            </button>
        </nav>
    </template>
    <h1 v-else class="tlp-pane-title">
        {{ $gettext("Table of contents") }}
    </h1>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { ARE_VERSIONS_DISPLAYED } from "@/can-user-display-versions-injection-key";

const { $gettext } = useGettext();

const are_versions_displayed = strictInject(ARE_VERSIONS_DISPLAYED);
</script>

<style scoped lang="scss">
h1 {
    display: flex;
    align-items: center;
    height: var(--artidoc-sidebar-title-height);
    margin: var(--artidoc-sidebar-title-vertical-margin) var(--tlp-medium-spacing);
}

.tlp-tabs {
    border-bottom: 1px solid var(--tlp-neutral-normal-color);

    // force border to be aligned with the one of the toolbar.
    box-shadow: none;
}
</style>
