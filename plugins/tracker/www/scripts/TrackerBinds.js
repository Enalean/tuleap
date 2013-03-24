/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

var codendi = codendi || { };
codendi.tracker = codendi.tracker || { };

codendi.tracker.bind = { };

codendi.tracker.bind.Editor = Class.create({
    initialize: function (element) {
        if (element) {
            $(element).select('input[type=text][name^="bind[edit]"]').each(this.editStaticValues.bind(this));
        } else {
            $$('input[type=text][name^="bind[edit]"]').each(this.editStaticValues.bind(this));
        }
        this.accordionForBindTypes();
        this.addNew();
    },
    /** 
     * wrap a text to a fixed width
     *
     * @param string text       the text to wrap
     * @param int    line_width the width of the paragraph
     * @param string separator  The line separator \n, <br />, ...
     *
     * @return string
     */
    wordwrap: function (text, line_width, sep) {
        var space_left = line_width;
        var s = [];
        text.split(" ").each(function (word) {
            if (word.length > space_left) {
                s.push(sep + word);
                space_left = line_width - word.length;
            } else {
                s.push(word);
                space_left = space_left - (word.length + 1);
            }
        });
        return s.join(' ');
    },
    //hide the textarea and textfield which update description and label of the value
    //replace them by a link. If the user click on the link, hide the link and show the fields
    editStaticValues: function (element) {
        var tf_label = element;
        var ta_description = element.up().down('textarea');
        var link = new Element('a', { href: '#', title: 'Edit ' + tf_label.value }).update(tf_label.value);
        var descr = new Element('div').addClassName('tracker-admin-bindvalue_description').update(this.wordwrap(ta_description.value, 80, '<br />'));
        tf_label.insert({before: link});
        link.insert({after: descr});
        link.observe('click', function (evt) {
            link.hide();
            descr.hide();
            tf_label.show();
            ta_description.show();
            evt.stop();
        });
        tf_label.hide();
        ta_description.hide();
    },
    accordionForBindTypes: function () {
        if ($('tracker-bind-factory')) {
            $('tracker-bind-factory').select('input[name="formElement_data[bind-type]"]').each(function (selector) {
                selector.observe('click', function () {
                    if (this.checked) {
                        this.up('#tracker-bind-factory').select('.tracker-bind-def').invoke('hide');
                        this.up('.tracker-bind-type').next('.tracker-bind-def').show();
                    }
                });
            });
            $('tracker-bind-factory').select('.tracker-bind-def').invoke('hide');
            ($('tracker-bind-factory').down('input[name="formElement_data[bind-type]"][checked="checked"]') ||
            $('tracker-bind-factory').down('input[name="formElement_data[bind-type]"]')).up('.tracker-bind-type')
                                                                                        .next('.tracker-bind-def')
                                                                                        .show();
        }
    },
    addNew: function () {
        var el = $('tracker-admin-bind-static-addnew');
        if (el) {
            var label = el.down().innerHTML;
            el.insert({
                before: 
                    new Element('a', {
                        href: '#',
                        title: label
                    }).update('<img src="' + codendi.imgroot + 'ic/add.png" /> ' + label).observe('click', function (evt) {
                        this.hide();
                        el.show();
                        evt.stop();
                    })
                }
            );
            el.hide();
        }
    }
});

document.observe('dom:loaded', function () {
    
    var e = new codendi.tracker.bind.Editor();
    
});

