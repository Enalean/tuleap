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
* along with Codendi; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* 
*/

/**
 * Hide references from the current item to other items
 */
function hide_references_to() {
    var references = $$(".reference_to");
    references.each(
        function(li) {
            // hide all <li> with class "reference_to"
            li.hide();
            if ( ! li.up().childElements().find(function(other_li) {
                                    return other_li.visible();
                                }
                            )) {
                // if no other <li> are visible, hide also <ul> and nature of the reference (previous)
                li.up().hide();
                li.up().previous().hide();
            }
        }
    );
    // display 'show link'
    if (references.size() > 0) {
        $('cross_references_legend').replace('<p id="cross_references_legend">'+ 
            codendi.getText('cross_ref_fact_include','legend_referenced_by') +
            ' <span><a href="#" onclick="show_references_to(); return false;">'+ 
            codendi.getText('cross_ref_fact_include','show_references_to') +
            '</span></p>');
    }
}

/**
 * Show references from the current item to other items
 */
function show_references_to() {
    var references = $$(".reference_to");
    references.each( 
	    function(li) {
	        // show all <li> with class "reference_to"
	        li.show();
	        // shwo also <ul> and nature of the reference (previous)
	        li.up().show();
	        li.up().previous().show();
	    }
	);
	// display 'hide link'
    if (references.size() > 0) {
        $('cross_references_legend').replace('<p id="cross_references_legend">'+ 
            codendi.getText('cross_ref_fact_include','legend') +
            ' <span><a href="#" onclick="hide_references_to(); return false;">'+ 
            codendi.getText('cross_ref_fact_include','hide_references_to') +
            '</span></p>');
    }
}

/**
*Show the delete icon for items
*/
function show_delete_icon(){
    
    
}

function delete_ref( id, message ){
    if(confirm(message)){
        var opt = {
            method: 'get',
            onComplete:function(){
                /*if current id has 1 sibling (the img), we hide the 'cross_reference'
                *else if current id has no sibling, we hide the reference nature
                *else we just hide the reference
                */
                if($(id).siblings().length==1  && $(id).up().siblings().length > 0){
                        $(id).up().hide();
                }else if($(id).up().siblings().length==0){
                        $(id).up('.nature').hide();
                }else {
                    $(id).hide();
                }
            }
        }
        new Ajax.Updater('id', $(id).down('.delete_ref').href, opt);
    }
    return false;
}

document.observe('dom:loaded', function() {
    
    //hide reference to item to clean the ui
    if ($('cross_references_legend')) {
        hide_references_to();
    }
    
    //hide the delete ref icon to clean the ui
    $$('.link_to_ref').each(function (l) {
        if (l.down('.delete_ref')) {
            var a = l.down('.delete_ref');
            var img = a.down('img');
            img.src = img.src.replace('cross.png', 'cross-disabled.png');
            img.observe('mouseover', function(evt) {
                img.src = img.src.replace('cross-disabled.png', 'cross.png');
            });
            img.observe('mouseout', function() {
                img.src = img.src.replace('cross.png', 'cross-disabled.png');
            });
        }
    });
});