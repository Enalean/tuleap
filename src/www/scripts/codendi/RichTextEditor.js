/**
 * Copyright (c) STMicroelectronics, 2010. All rights reserved
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
 */

var Codendi_RTE_Light = Class.create({
    initialize:function(element) {
        this.element = $(element);
        this.rte     = false;
        Element.insert(this.element, {before: '<div><a href="javascript:embedded_rte.toggle();">Toggle rich text formatting</a></div>'});
    },
    init_rte: function() {
        tinyMCE.init({
                // General options
                mode : "exact",
                elements : this.element.id,
                theme : "advanced",

                // Inherit language from Codendi default (see Layout class)
                language : useLanguage,

                // Theme options
                theme_advanced_toolbar_location : "top",
                theme_advanced_toolbar_align : "left",
                theme_advanced_statusbar_location : "bottom",
                theme_advanced_buttons1 : "bold,italic,blockquote,formatselect,image,|,bullist,numlist,|,link,unlink,|,code",
                theme_advanced_buttons2 : "",
                theme_advanced_buttons3 : "",
                theme_advanced_resizing : true,
                theme_advanced_blockformats : "p,pre",
                
                codendi:null //cheat to not have to remove the last comma in elements above. #*%@ IE !
        });
        this.rte = true;
    },
    toggle: function() {
        if (!this.rte) {
            this.init_rte();
        } else {
            if (!tinyMCE.get(this.element.id)) {
                tinyMCE.execCommand("mceAddControl", false, this.element.id);
            } else {
                tinyMCE.execCommand("mceRemoveControl", false, this.element.id);
            }
        }
    }
});