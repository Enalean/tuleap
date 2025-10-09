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
  -->

<template>
    <a
        v-if="commit.author !== null"
        class="commit-metadata-authored-name-link"
        v-bind:href="commit.author.user_url"
        data-test="known-author-name"
    >
        {{ commit.author.display_name }}
    </a>
    <span v-if="!commit.author" data-test="unknown-author-name">
        {{ commit.author_name }}
    </span>
</template>

<script setup lang="ts">
import type { PullRequestCommit } from "@tuleap/plugin-pullrequest-rest-api-types";

defineProps<{
    commit: PullRequestCommit;
}>();
</script>
