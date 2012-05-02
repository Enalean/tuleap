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
    initialize: function (element, url, options) {
        this.element = $(element);
        this.url     = url;
        this.options = Object.extend({
        }, options || { });
        
        this.fetching = false;
        this.fetched  = false;
        this.old_title = this.element.title;
        
        this.tooltip = false;
        
        this.showEvent = this.show.bindAsEventListener(this);
        this.element.observe('mouseover', this.showEvent);
        this.hideEvent = this.hide.bindAsEventListener(this);
        this.element.observe('mouseout', this.hideEvent);
    },
    fetch: function () {
        if (!this.fetching) {
            this.fetching = true;
            this.element.title = '';
            var req = new Ajax.Request(this.url, {
                onSuccess: (function (transport) {
                    this.fetching = false;
                    this.fetched  = true;
                    if (transport.responseText) {
                        this.createTooltip(transport.responseText);
                        if (this.show_tooltip) {
                            this.show();
                        }
                    } else {
                        this.element.title = this.old_title;
                    }
                }).bind(this)
            });
        }
    },
    createTooltip: function (content) {
        this.fetched = true;
        this.tooltip = new Element('div', {
                'class': "codendi-tooltip",
                style: "display:none;"
            }
        );
        this.tooltip.update(content);
        Element.insert(document.body, {
            bottom: this.tooltip
        });
    },
    show: function (evt) {
        this.show_tooltip = true;
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
        if (this.tooltip) {
            var pos = this.element.cumulativeOffset();
            Element.setStyle(this.tooltip, {
                    top: (pos[1] + this.element.offsetHeight) + "px",
                    left: pos[0] + "px"
                }
            );
            this.tooltip.show();
            if (evt) {
                //Event.stop(evt);
                Event.extend(evt);
                evt.preventDefault();
            }
        } else if (!this.fetched) {
            this.fetch();
        }
    },
    hide: function () {
        this.show_tooltip = false;
        if (this.tooltip) {
            this.timeout = setTimeout((function () { 
                this.tooltip.hide();
            }).bindAsEventListener(this), 200);
        }
    }
});

codendi.Tooltip.selectors = ['a[class=cross-reference]'];

codendi.Tooltip.load = function (element) {
    var sparkline_hrefs = { };
    
    $(element).select.apply($(element), codendi.Tooltip.selectors).each(function (a) {
        codendi.Tooltips.push(new codendi.Tooltip(a, a.href));
        //Create an array by hrefs : several 'a' for one href in order to reduce the requests
        if (sparkline_hrefs[a.href]) {
            sparkline_hrefs[a.href].push(a);
        } else {
            sparkline_hrefs[a.href] = [a];
        }        
    });
    
    //load sparklines
    if ($H(sparkline_hrefs).size()) {
        new Ajax.Request('/sparklines.php', {
            parameters: { 
                'sparklines[]': $H(sparkline_hrefs).keys()
            },
            onSuccess: function (transport) {
                if (transport.status == 200) {
                    $H(transport.responseJSON).each(function (element) {
                        var href      = element[0];
                        var sparkline = element[1];
                        //add the sparkline to each link
                        sparkline_hrefs[href].each(function(a) {
                                a.insert({
                                    top: new Element('img', {
                                        src: sparkline,
                                        style: 'vertical-align: middle; padding-right: 2px;',
                                        width: '10',
                                        height: '10'
                                    })
                                });
                        });
                    });
                    
                    
                }
            }
        });
    }
};

codendi.Tooltips = [];

document.observe('dom:loaded', function () {
    codendi.Tooltip.load(document.body);
});
