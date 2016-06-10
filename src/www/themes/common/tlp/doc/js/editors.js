/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

(function loadCodeMirrorEditors() {
    var demo_panels = document.querySelectorAll('.demo');

    [].forEach.call(demo_panels, function(demo_panel) {
        var textarea = demo_panel.querySelector('textarea'),
            example  = demo_panel.querySelector('.example');

        if (! textarea || ! example) {
            return;
        }

        var delay;
        var editor = CodeMirror.fromTextArea(textarea, {
            theme: 'mdn-like',
            lineNumbers: true,
            matchBrackets: true,
            mode: 'text/html',
            scrollbarStyle: 'overlay'
        });
        editor.on("change", function() {
            clearTimeout(delay);
            delay = setTimeout(updatePreview, 300);
        });

        function updatePreview() {
            example.innerHTML = editor.getValue();
        }
        setTimeout(updatePreview, 10);
    });
})();
