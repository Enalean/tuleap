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
    <document-layout v-if="is_sections_loading || (sections && sections.length > 0)" />
    <div v-else-if="!is_sections_loading && !sections" class="tlp-framed">
        <no-access-state />
    </div>
    <div v-else class="tlp-framed" data-test="states-section">
        <configuration-panel v-if="should_display_configuration_panel" />
        <empty-state v-else />
    </div>
</template>

<script setup lang="ts">
import EmptyState from "@/views/EmptyState.vue";
import { useInjectSectionsStore } from "@/stores/useSectionsStore";
import NoAccessState from "@/views/NoAccessState.vue";
import DocumentLayout from "@/components/DocumentLayout.vue";
import ConfigurationPanel from "@/components/configuration/ConfigurationPanel.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { computed } from "vue";

const { sections, is_sections_loading } = useInjectSectionsStore();

const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);

const { selected_tracker } = strictInject(CONFIGURATION_STORE);

const should_display_configuration_panel = computed(
    () => can_user_edit_document && !selected_tracker.value,
);
</script>
