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
    <div class="document-history-versions">
        <h2 class="document-history-section-title">{{ $gettext("Versions") }}</h2>
        <table class="tlp-table">
            <thead>
                <tr>
                    <th class="tlp-table-cell-numeric">{{ $gettext("Version") }}</th>
                    <th>{{ $gettext("Date") }}</th>
                    <th>{{ $gettext("Author") }}</th>
                    <th>{{ $gettext("Version name") }}</th>
                    <th>{{ $gettext("Change Log") }}</th>
                    <th>{{ $gettext("Approval") }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" class="tlp-table-cell-empty">
                        {{ $gettext("There are no versions yet") }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts"></script>
