<?php
require_once('pre.php');
header('Content-type: application/x-javascript');
?>

/**
 * Hide references from the current item to other items
 */
function hide_references_to() {
    $$(".reference_to").each(
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
    $('cross_references_legend').replace('<p id="cross_references_legend"><?php echo $GLOBALS['Language']->getText('cross_ref_fact_include','legend_referenced_by');?> <span><a href="#" onclick="show_references_to(); return false;"><?php echo $Language->getText('cross_ref_fact_include','show_references_to'); ?></span></p>');
}

/**
 * Show references from the current item to other items
 */
function show_references_to() {
    $$(".reference_to").each( 
	    function(li) {
	        // show all <li> with class "reference_to"
	        li.show();
	        // shwo also <ul> and nature of the reference (previous)
	        li.up().show();
	        li.up().previous().show();
	    }
	);
	// display 'hide link'
	$('cross_references_legend').replace('<p id="cross_references_legend"><?php echo $GLOBALS['Language']->getText('cross_ref_fact_include','legend');?> <span><a href="#" onclick="hide_references_to(); return false;"><?php echo $Language->getText('cross_ref_fact_include','hide_references_to'); ?></span></p>');
}

/**
*Show the delete icon for items
*/
function show_delete_icon(){
    document.observe('dom:loaded', function(){
             $$('.link_to_ref').each(function (l) {
                    l.down('.delete_ref').hide();
                    l.observe('mouseover', function() {
                            l.down('.delete_ref').show();
                    });
                    l.observe('mouseout', function() {
                        l.down('.delete_ref').hide();
                     });
                    }); 
                   });
}

function delete_ref( id, params,message ){
	if(confirm(message)){
        var opt = {
            method: 'get',
            parameters: params,
            /*onLoading: confirm(message),*/
            onComplete:function(l){
                if($(id).siblings().length==1  && $(id).up().siblings().length > 0){
                        $(id).up().hide();
                }else if($(id).up().siblings().length==0){
                        $(id).up('.nature').hide();
                }else $(id).hide();
            }
        }
        new Ajax.Updater('id', '../reference/rmreference.php', opt);
    }
}