/**
 * Copyright (c) Enalean SAS - 2013. All rights reserved
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

document.observe('dom:loaded', function () {
    var cache_duration_2_hours = 2 * 3600,
        cache_duration_1_week  = 7 * 24 * 3600,
        systray_collapse       = 'collapse',
        systray_expand         = 'expand';
        systray_collapse_key   = 'systray-collapse',
        collapse_classname     = 'systray-collapsed';

    // http://blog.anhangzhu.com/2011/07/20/html-5-local-storage-with-expiration/
    var AZHU = { }
    AZHU.storage = {
      save: function(key, jsonData, expirationSec){
        var expirationMS = expirationSec * 1000;
        var record = {value: JSON.stringify(jsonData), timestamp: new Date().getTime() + expirationMS}
        localStorage.setItem(key, JSON.stringify(record));
        return jsonData;
      },
      load: function(key){
        var record = JSON.parse(localStorage.getItem(key));
        if (!record){return false;}
        return (new Date().getTime() < record.timestamp && JSON.parse(record.value));
      }
    }

    if (! document.body.hasClassName('lab-mode')) {
        return;
    }

    createSystray();

    function createSystray() {
        var systray = '<div class="systray">' +
                    '<div class="systray_content">' +
                        '<img class="systray_icon" src="/themes/Tuleap/images/favicon.ico">' +
                        '<div class="systray_links"></div>' +
                    '</div>' +
                  '</div>';
        document.body.insert(systray);
        $$('.systray_icon').each(function (icon) {
            var systray = icon.up('.systray');

            loadTogglePreference(systray);
            loadLinks(systray);
            icon.observe('click', function (evt) {
                toggleSystray(systray)
            });
        });
    }

    function loadLinks(systray) {
        var systray_links = systray.down('.systray_links'),
            template      = new Template('<a href="#{href}">#{label}</a>');;

        new Ajax.Request('/systray.json', { onSuccess: getLinksFromJSONRequest });

        function getLinksFromJSONRequest(transport) {
            if (transport.responseJSON) {
                transport.responseJSON.each(function (link) {
                    systray_links.insert(template.evaluate(link));
                });
            }
        }
    }

    function loadTogglePreference(systray) {
        if (AZHU.storage.load(systray_collapse_key) === systray_collapse) {
            toggleSystray(systray);
        }
    }

    function toggleSystray(systray) {
        systray.toggleClassName(collapse_classname);
        saveTogglePreference(systray)
    }

    function saveTogglePreference(systray) {
        AZHU.storage.save(
            systray_collapse_key,
            systray.hasClassName(collapse_classname) ? systray_collapse : systray_expand,
            cache_duration_1_week
        );
    }
});
