<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
  -->

<template>
    <section v-if="item">
        <document-details-tabs v-bind:item="item" v-bind:active_tab="ApprovalTableTab" />

        <div class="tlp-framed-horizontally">
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">
                            <i class="fa-regular fa-fw fa-square-check" aria-hidden="true"></i>
                            {{ $gettext("Approval table") }}
                        </h1>
                    </div>

                    <section class="tlp-pane-section">
                        <div
                            v-if="!isAnApprovableDocument(item)"
                            class="tlp-alert-danger"
                            data-test="error-not-approvable"
                        >
                            {{ $gettext("This item cannot have an approval table") }}
                        </div>

                        <div v-else class="tlp-alert-warning">
                            <p class="tlp-alert-title">{{ $gettext("Under development") }}</p>
                            <a
                                class="tlp-button-primary"
                                role="button"
                                v-bind:href="getLinkToOldUI()"
                                data-test="old-ui-link"
                            >
                                <i
                                    class="fa-solid fa-shuffle tlp-button-icon"
                                    aria-hidden="true"
                                ></i>
                                {{ $gettext("Switch to old ui") }}
                            </a>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onBeforeMount, ref } from "vue";
import type { Item } from "../../type";
import { useActions } from "vuex-composition-helpers";
import DocumentDetailsTabs from "../Folder/DocumentDetailsTabs.vue";
import { ApprovalTableTab } from "../../helpers/details-tabs";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT } from "../../configuration-keys";
import { isAnApprovableDocument } from "../../helpers/approval-table-helper";

const props = defineProps<{
    item_id: number;
}>();

const item = ref<Item | null>(null);

const project = strictInject(PROJECT);

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);

onBeforeMount(async () => {
    item.value = await loadDocumentWithAscendentHierarchy(props.item_id);
});

function getLinkToOldUI(): string {
    return `/plugins/docman/?group_id=${project.id}&action=details&id=${props.item_id}&section=approval`;
}
</script>
