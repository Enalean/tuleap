/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

document.observe('dom:loaded', function (evt) {

    // gimme more results
    $$('.fts_results').each(function (results) {
        var link         = new Element('a', { href: '#' }).update('<if class="icon-chevron-down"></i> more'),
            button       = new Element('div').addClassName('fts_more').insert(link),
            parameters   = $(results.up('form')).serialize(true),
            initial_size = results.childElements().size();

        results.insert({after: button});
        link.observe('click', function (evt) {
            parameters['offset'] = results.childElements().size();

            // go get it! and inject in previous results
            new Ajax.Request('?', {
                method: 'POST',
                parameters: parameters,
                onSuccess: function (transport) {
                    var div = new Element('div').update(transport.responseText),
                        container = div.down('.fts_results'),
                        additional_results = container ? container.childElements() : [],
                        size = additional_results.length;

                    if (size) {
                        additional_results.each(function (result) {
                            results.insert(result);
                        });
                    }

                    if (size < initial_size) {
                        button.update('No more results.');
                    }
                }
            });

            Event.stop(evt);
            return false;
        });
    });
});

