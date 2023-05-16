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
    <section v-if="item && should_display_history_in_document">
        <div class="document-header tlp-framed-horizontally">
            <document-title-lock-info v-bind:item="item" v-bind:is-displaying-in-header="true" />

            <h1 class="document-header-title">{{ item.title }}</h1>
        </div>
        <nav class="tlp-tabs">
            <span class="tlp-tab tlp-tab-active">{{ $gettext("Versions") }}</span>
            <router-link
                class="tlp-tab"
                v-bind:to="{ name: 'history', params: { item_id: item.id } }"
                >{{ $gettext("Logs") }}</router-link
            >
        </nav>
        <div class="tlp-framed-horizontally">
            <div class="tlp-alert-success" v-if="success_feedback">
                {{ success_feedback }}
            </div>
            <history-versions v-if="item_type_has_versions" v-bind:item="item" />
            <div class="tlp-alert-danger" v-else>
                {{ $gettext("This item is not versionable") }}
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import DocumentTitleLockInfo from "../Folder/LockInfo/DocumentTitleLockInfo.vue";
import { useRoute } from "vue-router";
import { useActions } from "vuex-composition-helpers";
import { onBeforeMount, provide, ref } from "vue";
import type { Item } from "../../type";
import HistoryVersions from "./HistoryVersions.vue";
import { isEmbedded, isFile, isLink } from "../../helpers/type-check-helper";
import { FEEDBACK, SHOULD_DISPLAY_HISTORY_IN_DOCUMENT } from "../../injection-keys";
import { strictInject } from "@tuleap/vue-strict-inject";

const success_feedback = ref<string | null>(null);

provide(FEEDBACK, {
    success: (feedback: string | null) => {
        success_feedback.value = feedback;
    },
});

const should_display_history_in_document = strictInject(SHOULD_DISPLAY_HISTORY_IN_DOCUMENT);

const item = ref<Item | null>(null);
const item_type_has_versions = ref(false);

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);

const route = useRoute();
onBeforeMount(async () => {
    item.value = await loadDocumentWithAscendentHierarchy(parseInt(route.params.item_id, 10));
    if (item.value) {
        item_type_has_versions.value =
            isFile(item.value) || isLink(item.value) || isEmbedded(item.value);
    }
});
</script>
