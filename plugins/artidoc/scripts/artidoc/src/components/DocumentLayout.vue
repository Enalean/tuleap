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
    <div class="document-layout">
        <document-sidebar />
        <section class="document-content" data-test="document-content">
            <document-content />
        </section>
    </div>
</template>

<script setup lang="ts">
import { provide, ref } from "vue";
import DocumentContent from "./DocumentContent.vue";
import DocumentSidebar from "./sidebar/DocumentSidebar.vue";
import { CURRENT_VERSION_DISPLAYED } from "./current-version-displayed";
import { Option } from "@tuleap/option";
import type { Version } from "./sidebar/versions/fake-list-of-versions";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    CAN_USER_EDIT_DOCUMENT,
    ORIGINAL_CAN_USER_EDIT_DOCUMENT,
} from "@/can-user-edit-document-injection-key";

let old_version = ref<Option<Version>>(Option.nothing());
const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);
const original_can_user_edit_document = strictInject(ORIGINAL_CAN_USER_EDIT_DOCUMENT);

provide(CURRENT_VERSION_DISPLAYED, {
    old_version,
    switchToOldVersion(version: Version) {
        old_version.value = Option.fromValue(version);
        can_user_edit_document.value = false;
    },
    switchToLatestVersion() {
        old_version.value = Option.nothing();
        can_user_edit_document.value = original_can_user_edit_document;
    },
});
</script>

<style lang="scss" scoped>
@use "@/themes/includes/size";
@use "@/themes/includes/viewport-breakpoint";

.document-layout {
    $content-column: calc(100% * #{size.$document-container-width-ratio});
    $sidebar-column: calc(100% * (1 - #{size.$document-container-width-ratio}));

    display: grid;
    grid-template-columns: 100% 0;
    transition: grid-template-columns ease-in-out 250ms;

    &:has(.is-aside-expanded) {
        grid-template-columns: $content-column $sidebar-column;
    }
}

.document-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: var(--tlp-fade-background-color-darker-01);
}

@media screen and (max-width: #{viewport-breakpoint.$small-screen-size}) {
    .document-layout,
    .document-layout:has(.is-aside-expanded) {
        grid-template-columns: 1fr;
        grid-template-rows: max-content auto;
        height: inherit;
    }

    .document-content {
        border-right: 0;
    }
}
</style>
