<!--
  - Copyright (c) Enalean, 2024-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="empty-state-page">
        <div class="empty-state-illustration">
            <project-ongoing-creation-svg />
        </div>

        <h1 class="empty-state-title">
            {{ $gettext("Your project is being created.") }}
        </h1>
        <p class="empty-state-text" v-dompurify-html="message_creation"></p>

        <a class="tlp-button-primary tlp-button-large empty-state-action" href="/my/">
            <i class="fa-solid fa-reply tlp-button-icon"></i>
            <span>{{ $gettext("Go to my home page") }}</span>
        </a>
    </div>
</template>

<script setup lang="ts">
import ProjectOngoingCreationSvg from "./ProjectOngoingCreationSvg.vue";
import { computed } from "vue";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const gettext_provider = useGettext();

const message_creation = computed((): string => {
    return gettext_provider.$gettext("You will receive an email when the project is created.");
});
</script>
