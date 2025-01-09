<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <section class="empty-state-pane">
        <svg
            width="136"
            height="106"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            class="empty-state-illustration"
        >
            <circle
                opacity=".3"
                cx="68.5"
                cy="53"
                r="53"
                fill="var(--tlp-illustration-grey-on-background)"
            />
            <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M56.5 27h-54a1 1 0 00-1 1v8a1 1 0 001 1h54a1 1 0 001-1v-8a1 1 0 00-1-1zm-54-1a2 2 0 00-2 2v8a2 2 0 002 2h54a2 2 0 002-2v-3.5h4.25a3.25 3.25 0 010 6.5h-9.5a4.25 4.25 0 00-4.248 4.373A4.452 4.452 0 0049 43.5V58a4.5 4.5 0 004.5 4.5h3.293l-2.328 2.328a.5.5 0 10.707.707l3.182-3.181A.499.499 0 0058.5 62V66a2 2 0 002 2h41a2 2 0 002-2v-3.5h4.25a3.25 3.25 0 110 6.5h-19.5a4.25 4.25 0 000 8.5h3.543l-2.329 2.328a.5.5 0 10.708.707l3.182-3.181a.5.5 0 000-.708l-3.182-3.181a.5.5 0 10-.707.707l2.328 2.328H88.25a3.25 3.25 0 010-6.5h19.5a4.25 4.25 0 000-8.5h-4.25V58a2 2 0 00-2-2h-41a2 2 0 00-2 2v3.999a.499.499 0 00-.146-.353l-3.182-3.181a.5.5 0 10-.707.707l2.328 2.328H53.5A3.5 3.5 0 0150 58V45.989a4.241 4.241 0 003.25 1.511h3.543l-2.328 2.328a.5.5 0 10.707.707l3.182-3.181A.499.499 0 0058.5 47V51a2 2 0 002 2h33a2 2 0 002-2v-8a2 2 0 00-2-2h-33a2 2 0 00-2 2v3.999a.499.499 0 00-.146-.353l-3.182-3.181a.5.5 0 10-.707.707l2.328 2.328H53.25a3.25 3.25 0 01-3.248-3.127A3.5 3.5 0 0153.5 40h9.25a4.25 4.25 0 000-8.5H58.5V28a2 2 0 00-2-2h-54zm131 46h-38a1 1 0 00-1 1v8a1 1 0 001 1h38a1 1 0 001-1v-8a1 1 0 00-1-1zm-38-1a2 2 0 00-2 2v8a2 2 0 002 2h38a2 2 0 002-2v-8a2 2 0 00-2-2h-38zm-35-29h33a1 1 0 011 1v8a1 1 0 01-1 1h-33a1 1 0 01-1-1v-8a1 1 0 011-1zm41 15h-41a1 1 0 00-1 1v8a1 1 0 001 1h41a1 1 0 001-1v-8a1 1 0 00-1-1zm-98-29a1 1 0 00-1 1v6a1 1 0 001 1h52a1 1 0 001-1v-6a1 1 0 00-1-1h-52zm92 46a1 1 0 011-1h5a1 1 0 011 1v6a1 1 0 01-1 1h-5a1 1 0 01-1-1v-6zm-34-31a1 1 0 00-1 1v6a1 1 0 001 1h22a1 1 0 001-1v-6a1 1 0 00-1-1h-22zm-1 16a1 1 0 011-1h11a1 1 0 011 1v6a1 1 0 01-1 1h-11a1 1 0 01-1-1v-6z"
                fill="var(--tlp-illustration-main-color)"
            />
            <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M45 17a2.5 2.5 0 100-5 2.5 2.5 0 000 5zm0 0h.5v83.5h-1V17h.5z"
                fill="var(--tlp-illustration-yellow)"
            />
        </svg>
        <p class="empty-state-text">
            <span> {{ $gettext("There is no data to show.") }}</span>
            <span v-if="should_invite_to_come_back">{{ $gettext("Please come back later.") }}</span>
        </p>
    </section>
</template>

<script setup lang="ts">
defineProps<{ should_invite_to_come_back: boolean }>();
</script>
