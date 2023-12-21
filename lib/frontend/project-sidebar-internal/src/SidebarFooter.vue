<!--
  - Copyright (c) 2021-Present Enalean
  -
  - Permission is hereby granted, free of charge, to any person obtaining a copy
  - of this software and associated documentation files (the "Software"), to deal
  - in the Software without restriction, including without limitation the rights
  - to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  - copies of the Software, and to permit persons to whom the Software is
  - furnished to do so, subject to the following conditions:
  -
  - The above copyright notice and this permission notice shall be included in all
  - copies or substantial portions of the Software.
  -
  - THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  - IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  - FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  - AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  - LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  - OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  - SOFTWARE.
  -->

<template>
    <a
        href="https://www.tuleap.org/?utm_source=forge&utm_medium=forge&utm_campaign=forge"
        class="sidebar-about"
        v-if="config"
    >
        <svg class="sidebar-about-logo" viewBox="0 0 172 50" xmlns="http://www.w3.org/2000/svg">
            <title>{{ config.instance_information.version.full_descriptive_version }}</title>
            <path
                d="M54.52 9.38v4.8h-3.65v4.8h3.65v11.9c-.01 3.45.62 5.81 2.05 7.33 1.21 1.27 3.16 2.06 5.48 2.06 2.11 0 3.86-.32 4.79-.7v-4.8c-.83.14-1.46.2-2.51.23-2.6-.04-3.44-1.61-3.42-4.8V18.99h6.16v-4.8H60.9V7.54l-6.39 1.83zm35.13 5.03h-2.5v15.56c0 1.04-.21 2.03-.47 2.75-1.1 2.57-3.78 5.12-7.52 5.03-4.93.1-6.6-3.82-6.62-9.15V14.4h-2.5v14.65c0 9.36 5.4 10.98 8.66 10.98 4.4 0 7.3-2.72 8.44-5.04h.23l.23 4.35h2.28c-.16-1.82-.21-3.8-.23-5.95V14.41zm6.39 25.17h2.5V2.28h-2.5v37.3zm27.6-13.04v-1.37c0-3.67-1.58-11.44-9.81-11.44-6.56 0-11.4 5.25-11.4 13.72 0 7.34 4.52 12.59 11.86 12.59 4.35 0 6.93-1.1 7.98-1.6l-.68-2.06c-1.48.77-3.37 1.5-7.08 1.6-5.19-.1-9.61-3.3-9.58-11.44h18.7zm-18.48-2.06c.48-3.83 2.85-8.7 8.44-8.7 6.1 0 7.58 5.23 7.53 8.7h-15.97zm38.33-.92c.08-4.38-1.35-9.83-8.67-9.83-2.35 0-5.21.63-7.53 2.06l.68 1.83a12.93 12.93 0 016.62-1.83c5.95-.02 6.43 4.96 6.39 7.32v.68c-10.36 0-15.52 3.51-15.52 9.38 0 3.3 2.34 6.87 7.53 6.87 4.05 0 6.86-2.26 8.21-4.12l.46 3.43h2.28c-.32-1.83-.37-3.87-.45-5.95v-9.84zm-2.51 8.01c0 .31-.06.88-.23 1.37-.88 2.48-3.3 5.04-7.53 5.04-2.6 0-5.02-1.62-5.02-5.26 0-5.94 7.26-6.78 12.78-6.64v5.5zm8.2 18.3h2.52v-14.4h.23c1.64 2.97 4.87 4.6 8.44 4.57 6.23.02 11.63-4.6 11.63-13.5 0-7.46-4.4-12.81-10.95-12.81-4.41 0-7.54 2.04-9.35 5.26h-.23V14.4h-2.51c.16 2.3.21 4.77.23 8.01v27.45zm2.52-25.16c0-.77.21-1.56.45-2.29a8.7 8.7 0 018.44-6.63c5.71 0 8.9 5.1 8.9 10.75 0 6.76-3.34 11.44-9.12 11.44-4 0-7.4-2.53-8.44-6.4a7.8 7.8 0 01-.23-2.07v-4.8z"
                fill="#fff"
            />
            <path
                class="tuleap-logo"
                d="M22.95 19.58c-.94.86-4.06 3.6-4.06 3.6-1.3 1.16-2.16 1.99-2.76 2.7-.03 0-5.07-2.73-5.48-2.98.36-.85.84-1.97 1.18-2.71.61-1.32 1.78-3.35 1.78-3.35s-1.06-1.59-2.37-3.35c-1.32-1.76-3.17-3.35-4.93-3.35-1.75 0-3.27 4.05-3.27 4.05s-.18 2.74 2.1 5.65a16.2 16.2 0 004.98 4.21c.41.24 5.05 2.74 5.27 2.85a14.49 14.49 0 00-1.78 11.16c.16.68.45 1.42.84 2.22.36.74.8 1.52 1.3 2.32a36.97 36.97 0 002.01 2.85c-2.88-.27-5.47-1.72-7.16-2.78C-1 35.35-2.56 22.04 3.4 11.37c3.9-6.94 10.7 4.68 10.7 4.68s2.82-4.59 8.7-10.23c.5.76.89 1.67 1.44 2.79C26.7 1.5 27.65.05 29.23 0c4.37-.14 11.43 14.36 7.92 27.58-2.36 8.87-11.96 17.23-17.78 17.86a35.72 35.72 0 01-2.58-3.52c-.48-.76-.9-1.5-1.23-2.2a10.4 10.4 0 01-.76-1.97c-.89-3.56-.23-7.84 2.09-10.85.54-.7 1.4-1.55 2.82-2.81-.01 0 3.11-2.74 4.07-3.61 4.2-3.84 6.65-6.92 7.54-10.1 1.22-3.78 1.21-6.78.84-7.3 0 0-1.7-2.3-2.84-2.2-1.14.08-2.17 2.37-2.35 2.81-.17.44-.97 2.47-2.2 5.73.2.44.83 2.04 1.48 3.62.3.73.6 1.57.84 2.29a45.42 45.42 0 01-4.14 4.24z"
                fill="#D3D7E1"
            />
        </svg>
        <svg
            class="sidebar-about-logo-without-product-name"
            viewBox="0 0 42 50"
            xmlns="http://www.w3.org/2000/svg"
        >
            <title>{{ config.instance_information.version.full_descriptive_version }}</title>
            <path
                class="tuleap-logo"
                d="M22.95 19.58c-.94.86-4.06 3.6-4.06 3.6-1.3 1.16-2.16 1.99-2.76 2.7-.03 0-5.07-2.73-5.48-2.98.36-.85.84-1.97 1.18-2.71.61-1.32 1.78-3.35 1.78-3.35s-1.06-1.59-2.37-3.35c-1.32-1.76-3.17-3.35-4.93-3.35-1.75 0-3.27 4.05-3.27 4.05s-.18 2.74 2.1 5.65a16.2 16.2 0 004.98 4.21c.41.24 5.05 2.74 5.27 2.85a14.49 14.49 0 00-1.78 11.16c.16.68.45 1.42.84 2.22.36.74.8 1.52 1.3 2.32a36.97 36.97 0 002.01 2.85c-2.88-.27-5.47-1.72-7.16-2.78C-1 35.35-2.56 22.04 3.4 11.37c3.9-6.94 10.7 4.68 10.7 4.68s2.82-4.59 8.7-10.23c.5.76.89 1.67 1.44 2.79C26.7 1.5 27.65.05 29.23 0c4.37-.14 11.43 14.36 7.92 27.58-2.36 8.87-11.96 17.23-17.78 17.86a35.72 35.72 0 01-2.58-3.52c-.48-.76-.9-1.5-1.23-2.2a10.4 10.4 0 01-.76-1.97c-.89-3.56-.23-7.84 2.09-10.85.54-.7 1.4-1.55 2.82-2.81-.01 0 3.11-2.74 4.07-3.61 4.2-3.84 6.65-6.92 7.54-10.1 1.22-3.78 1.21-6.78.84-7.3 0 0-1.7-2.3-2.84-2.2-1.14.08-2.17 2.37-2.35 2.81-.17.44-.97 2.47-2.2 5.73.2.44.83 2.04 1.48 3.62.3.73.6 1.57.84 2.29a45.42 45.42 0 01-4.14 4.24z"
                fill="#D3D7E1"
            />
        </svg>

        <span class="sidebar-about-version">
            {{ config.instance_information.version.flavor_name }}
            <br />
            {{ config.instance_information.version.version_identifier }}
        </span>
    </a>
    <span
        v-if="config && config.instance_information.copyright !== null"
        class="sidebar-copyright"
        data-test="copyright"
    >
        {{ config.instance_information.copyright }}
    </span>
</template>
<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { SIDEBAR_CONFIGURATION } from "./injection-symbols";

const config = strictInject(SIDEBAR_CONFIGURATION);
</script>
