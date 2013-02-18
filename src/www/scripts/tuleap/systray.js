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

var tuleap = tuleap || { };
tuleap.systray = {
    load: function(body, storage) {
        var cache_duration_2_hours    = 2 * 3600,
            cache_duration_1_week     = 7 * 24 * 3600,
            systray_collapse          = 'collapse',
            systray_expand            = 'expand',
            systray_collapse_cachekey = 'systray-collapse',
            collapse_classname        = 'systray-collapsed',
            systray_links_cachekey    = 'systray-links';

        if (! body.hasClassName('lab-mode')) {
            // we assume that anonymous user is never in lab-mode
            // In order to avoid security issues regarding links, we
            // assume that on logout user will fall in anonymous mode.
            clearLinksCache();
            return;
        }

        loadLinks();

        function loadLinks() {
            var links = storage.load(systray_links_cachekey);

            if (links) {
                createSystray(links);
            } else {
                new Ajax.Request('/systray.json', { method: 'GET', onSuccess: getLinksFromJSONRequest });
            }
        }

        function getLinksFromJSONRequest(transport) {
            var links = transport.responseJSON;

            if (links) {
                saveLinks(links);
                createSystray(links);
            }
        }

        function createSystray(links) {
            if (links.length === 0) {
                return;
            }

            var systray = insertSystrayInBody();

            loadTogglePreference(systray);
            registerCollapseEvent(systray);
            insertLinks(systray, links);
        }

        function insertSystrayInBody() {
            var systray_html = '<div class="systray">' +
                    '<div class="systray_content">' +
                        '<img class="systray_icon" src="/themes/Tuleap/images/ic/systray.png">' +
                        '<div class="systray_links dropup"></div>' +
                    '</div>' +
                  '</div>',
                systray = body.insert(systray_html).select('.systray')[0];
            return systray;
        }

        function insertLinks(systray, links) {
            var menu,
                systray_links = systray.down('.systray_links'),
                link_template = new Template('<a href="#{href}">#{label}</a>'),
                first_link    = link_template.evaluate(links.shift()),
                dropdown      = '<div class="dropdown">' + first_link +
                                    '<a class="dropdown-toggle" data-toggle="dropdown" href="#"> <i class="icon-angle-up"></i> </a>' +
                                    '<ul class="dropdown-menu" role="menu"></ul>' +
                                '</div>';

            systray_links.update(dropdown);
            menu = systray_links.down('.dropdown-menu');

            links.each(function (link) {
                menu.insert('<li>' + link_template.evaluate(link) + '</li>');
            });
        }

        function registerCollapseEvent(systray) {
            var icon = systray.down('.systray_icon');

            icon.observe('click', function (evt) {
                toggleSystray(systray)
            });
        }

        function saveLinks(links) {
            storage.save(
                systray_links_cachekey,
                links,
                cache_duration_2_hours
            );
        }

        function loadTogglePreference(systray) {
            if (storage.load(systray_collapse_cachekey) === systray_collapse) {
                toggleSystray(systray);
            }
        }

        function toggleSystray(systray) {
            systray.toggleClassName(collapse_classname);
            saveTogglePreference(systray)
        }

        function saveTogglePreference(systray) {
            storage.save(
                systray_collapse_cachekey,
                systray.hasClassName(collapse_classname) ? systray_collapse : systray_expand,
                cache_duration_1_week
            );
        }

        function clearLinksCache() {
            storage.save(systray_links_cachekey, []);
        }
    }
}
