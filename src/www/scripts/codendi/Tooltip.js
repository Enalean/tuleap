/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
*
* Originally written by Nicolas Terray, 2008
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
* along with Codendi; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* 
*/

var codendi = codendi || { };

codendi.Tooltip = Class.create({
    initialize: function(element, url, options) {
        this.element = $(element);
        this.url     = url;
        this.options = Object.extend({
        }, options || { });
        
        this.fetched = false;
        this.fetching = false;
        
        this.tooltip = false;
        
        this.showEvent = this.show.bindAsEventListener(this);
        this.element.observe('mouseover', this.showEvent);
        this.hideEvent = this.hide.bindAsEventListener(this);
        this.element.observe('mouseout', this.hideEvent);
    },
    fetch: function() {
        if (!this.fetching) {
            this.fetching = true;
            this.element.title = '';
            new Ajax.Request(this.url, {
                onSuccess:(function(transport) {
                    this.createTooltip(transport.responseText);
                    this.fetching = false;
                    this.show();
                }).bind(this)
            });
        }
    },
    createTooltip: function(content) {
        var pos = this.element.cumulativeOffset();
        this.tooltip = new Element('div', {
                style: "z-index:1000; font-size:0.8em; background-color:#ffffcc; border:1px solid gray; display:none; position:absolute; padding:4px 8px; top:"+(pos[1] + this.element.offsetHeight)+"px; left:"+pos[0]+"px;"
        });
        this.tooltip.update(content);
        document.body.insert({
            bottom: this.tooltip
        });
    },
    show: function() {
        if (this.tooltip) {
            this.tooltip.show();
        } else {
            this.fetch();
        }
    },
    hide: function() {
        if (this.tooltip) {
            this.tooltip.hide();
        }
    }
});

document.observe('dom:loaded', function() {
    $$('a[class=cross-reference]').each(function (a) {
        new codendi.Tooltip(a, a.href);
    });
});
