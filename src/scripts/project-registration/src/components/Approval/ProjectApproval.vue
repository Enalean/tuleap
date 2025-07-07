<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="empty-state-page">
        <div class="empty-state-illustration">
            <project-approval-svg />
        </div>

        <h1 class="empty-state-title">
            {{ $gettext("Your project has been submitted to the administrators for validation") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("You will receive an email when the administrator has validated it.") }}
        </p>

        <a class="tlp-button-primary tlp-button-large empty-state-action" href="/my/"
            ><i class="fa-solid fa-reply tlp-button-icon" aria-hidden="true"></i
            >{{ $gettext("Go to my home page") }}</a
        >
    </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useGettext } from "vue3-gettext";
import { useStore } from "../../stores/root";
import ProjectApprovalSvg from "./ProjectApprovalSvg.vue";
import { useRouter } from "../../helpers/use-router";

const root_store = useStore();
const router = useRouter();
const { $gettext } = useGettext();

onMounted(() => {
    if (!root_store.is_template_selected) {
        router.push("new");
    }
});
</script>
