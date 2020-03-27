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

/* global Class:readonly $:readonly $$:readonly */

var codendi = codendi || {};

codendi.dropdown_panels = [];
codendi.DropDownPanel = Class.create({
    initialize: function (element, handle) {
        this.element = $(element);
        this.handle = $(handle);
        if (this.element && this.handle) {
            this.handle.observe("click", this.toggle.bindAsEventListener(this));
            var reset_btn = this.element.down("input[type=reset]");
            if (reset_btn) {
                reset_btn.observe("click", this.reset.bind(this));
            }

            //Disable submit button until there is one radio checked
            this.radios = this.element.select("input[type=radio]");
            if (this.radios.size()) {
                this.radios.each(function (input) {
                    input.checked = false;
                });
                this.element.down("input[type=submit]").disable();
            }

            //Focus text fields
            this.element
                .select("input[type=radio]")
                .invoke("observe", "click", this.check.bindAsEventListener(this));

            this.element.up().makePositioned();
            this.element.absolutize();
            this.element.setStyle({
                top: this.handle.offsetHeight + "px",
            });

            //hide the thing if the user click elsewhere
            document.observe(
                "click",
                function (evt) {
                    if (!Event.findElement(evt, "#" + this.element.identify())) {
                        if (this.element.visible() && !evt.isRightClick()) {
                            this.reset();
                        }
                    }
                }.bind(this)
            );

            this.element.hide();
        }
        codendi.dropdown_panels.push(this);
    },
    hide: function () {
        this.element.hide();
    },
    toggle: function (evt) {
        if (this.element.visible()) {
            this.element.hide();
        } else {
            //Hide all others
            codendi.dropdown_panels.invoke("reset");

            //Display me
            this.element.setStyle({
                top: this.handle.offsetHeight + "px",
                left: this.handle.offsetLeft + "px",
            });
            this.element.show();
            var over = this.element.offsetWidth + this.element.cumulativeOffset().left;
            if (over > document.body.offsetWidth) {
                this.element.setStyle({
                    left:
                        this.handle.offsetLeft +
                        this.handle.offsetWidth -
                        this.element.offsetWidth +
                        "px",
                });
            }
        }
        Event.stop(evt);
    },
    reset: function () {
        this.element.hide();
        if (this.radios.size()) {
            this.radios.each(function (input) {
                input.checked = false;
            });
            this.element.down("input[type=submit]").disable();
        }
    },
    check: function (evt) {
        this.element.down("input[type=submit]").enable();
        var el = Event.element(evt).up().down("input[type=text]");
        if (el) {
            el.activate();
        }
    },
});

//Auto load
document.observe("dom:loaded", function () {
    $$(".dropdown_panel").each(function (element) {
        if (element.id && $(element.id + "_handle")) {
            //eslint-disable-next-line @typescript-eslint/no-unused-vars
            var d = new codendi.DropDownPanel(element, $(element.id + "_handle"));
        }
    });
});
