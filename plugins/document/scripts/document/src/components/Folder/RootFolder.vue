<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -
  -->

<template>
    <folder-container />
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useActions, useMutations } from "vuex-composition-helpers";
import FolderContainer from "./FolderContainer.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT } from "../../configuration-keys";

const project = strictInject(PROJECT);

const { loadRootFolder, removeQuickLook } = useActions(["loadRootFolder", "removeQuickLook"]);

const { resetAscendantHierarchy } = useMutations(["resetAscendantHierarchy"]);

onMounted((): void => {
    loadRootFolder(project.id);
    resetAscendantHierarchy();
    removeQuickLook();
});
</script>
