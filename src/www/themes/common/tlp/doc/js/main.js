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

(function colorSwitcher() {
    var color_switchers = document.querySelectorAll('.color-switcher > li'),
        stylesheet      = document.getElementById('tlp-stylesheet');

    [].forEach.call(color_switchers, function(color_switcher) {
        color_switcher.addEventListener('click', function(event) {
            if (! this.classList.contains('active')) {
                var color                 = this.classList[0].replace('switch-to-', '');
                var active_color_switcher = document.querySelector('.color-switcher > li.active');

                active_color_switcher.classList.remove('active');

                this.classList.add('active');

                document.body.classList.remove('orange', 'blue', 'green', 'red', 'grey', 'purple');
                document.body.classList.add(color);

                loadStylesheet(color);
            }
        });
    });

    updateAllHexaColors();

    function loadStylesheet(color) {
        stylesheet.textContent = '';
        var interval = setInterval(function() {
            if (stylesheet.sheet.cssRules.length) {
                updateAllHexaColors();
                clearInterval(interval);
            }
        }, 10);
        stylesheet.textContent = '@import "../dist/tlp-' + color + '.min.css"';
    }

    function updateAllHexaColors() {
        updateHexaColor('info');
        updateHexaColor('success');
        updateHexaColor('warning');
        updateHexaColor('danger');
    }

    function updateHexaColor(name) {
        var element = document.querySelector('.doc-color-' + name)
        var color = document.defaultView.getComputedStyle(element, null).getPropertyValue('background-color');
        if (color.search("rgb") !== -1) {
            color = color.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            color = "#" + hex(color[1]) + hex(color[2]) + hex(color[3]);
        }
        document.querySelector('.doc-color-' + name + '-hexacode').innerHTML = color;
    }

    function hex(x) {
        return ("0" + parseInt(x).toString(16)).slice(-2);
    }
})();

window.toggleMargins = function (id) {
    document.getElementById(id).classList.toggle('example-hide-margins');
}

window.onscroll = function scrollspy() {
    var sections        = document.querySelectorAll('.doc-section'),
        sections_offset = {};

    [].forEach.call(sections, function(section) {
        if (section.id) {
            sections_offset[section.id] = section.offsetTop;
        }
    });

    var scrollPosition = document.documentElement.scrollTop || document.body.scrollTop;

    for (id in sections_offset) {
        if (sections_offset[id] <= scrollPosition + 50) {
            var sub_nav_item_active = document.querySelector('.sub-nav-item.active');
            if (sub_nav_item_active) {
                sub_nav_item_active.classList.remove('active');
            }

            var nav_item_active = document.querySelector('.nav-item.active');
            if (nav_item_active) {
                nav_item_active.classList.remove('active');
            }

            var nav_item_pointed = document.querySelector('.nav-item > a[href*=' + id + ']').parentNode;
            nav_item_pointed.classList.add('active');

            if (nav_item_pointed.classList.contains('sub-nav-item')) {
                nav_item_pointed.closest('.nav-item:not(.sub-nav-item)').classList.add('active');
            }
        }
    }
};
