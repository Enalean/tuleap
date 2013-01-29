document.observe('dom:loaded', function () {
    $$('.cardwall_board').each(function ( board ) {
        (function checkForLatestCardWallVersion() {
            if ($('tracker_report_cardwall_to_be_refreshed')) {
                //    Eg: board > drag n drop > go to a page > click back (the post it should be drag 'n dropped)
                if ($F('tracker_report_cardwall_to_be_refreshed') === "1") {
                    $('tracker_report_cardwall_to_be_refreshed').value = 0;
                    location.reload();
                } else {
                    $('tracker_report_cardwall_to_be_refreshed').value = 1;
                }
            }
        })();

        (function defineDraggableCards(){
            board.select('.cardwall_board_postit').each(function (postit) {
                new Draggable(postit, {
                    revert: 'failure',
                    delay: 175
                });
            });
        })();

        (function dragAndDropColumns(){
            var cols = board.select( 'col' );

            cols.each( function( col, col_index ) {
                var table_rows = col.up( 'table' ).down( 'tbody.cardwall' ).childElements();

                table_rows.each( function( tr ) {
                    var value_id     = col.id.split( '-' )[ 1 ],
                        swimline_id  = tr.id.split( '-' )[ 1 ],
                        current_td   = tr.down( 'td.cardwall-cell', col_index ),
                        accept_class = 'drop-into-' + swimline_id + '-' + value_id;

                    tr.childElements()[ col_index ]
                        .select( '.cardwall_board_postit' )
                        .invoke( 'removeClassName', accept_class );

                    Droppables.add( current_td, {
                        hoverclass: 'cardwall_board_column_hover',
                        accept: accept_class,

                        onDrop: function (dragged, dropped, event) {
                            var value_id       = col.id.split( '-' )[ 1 ],
                                new_column     = dragged.up( 'tr' ).childElements().indexOf( dragged.up( 'td' ) ),
                                new_column_id  = cols[ new_column ].id.split( '-' )[ 1 ],
                                new_class_name = 'drop-into-' + swimline_id + '-' + new_column_id;

                            //change the classname of the post it to be accepted by the formers columns
                            dragged.addClassName( new_class_name );
                            dragged.removeClassName( accept_class );

                            //switch to the new column
                            dragged.remove();
                            current_td.down( 'ul' ).appendChild( dragged );

                            setStyle( dragged, current_td );
                            ajaxUpdate( dragged, value_id );
                        }
                    });
                });
            });

            function setStyle( dragged, current_td ) {
                var restore_color   = current_td.getStyle( 'background-color' );
                
                dragged.setStyle({
                    left: 'auto',
                    top : 'auto'
                });
                
                new Effect.Highlight(current_td, {
                    restorecolor: restore_color
                });
            }

            function ajaxUpdate( dragged, value_id ) {
                var field_id,
                    parameters = {};

                if ($( 'tracker_report_cardwall_settings_column' )) {
                    field_id = $F( 'tracker_report_cardwall_settings_column' );
                } else {
                    field_id = dragged.readAttribute( 'data-column-field-id' );
                    value_id = $F( 'cardwall_column_mapping_' + value_id + '_' + field_id );
                }

                parameters[ 'aid' ]  = dragged.id.split( '-' )[ 1 ];
                parameters[ 'func' ] = 'artifact-update';
                parameters[ 'artifact[' + field_id + ']' ] = value_id;

                //save the new state
                new Ajax.Request( codendi.tracker.base_url, {
                    method : 'POST',
                    parameters : parameters,
                    onComplete : afterAjaxUpdate
                });
            }

            function afterAjaxUpdate( response ) {
                $H( response.responseJSON ).each( function ( card ) {
                    var card_element = $( 'cardwall_board_postit-' + card.key );
                    if ( card_element === null ) {
                        return
                    }

                    $H( card.value ).each( function( field ) {
                        var field_name = '.valueOf_' + field.key;
                        card_element.select( field_name ).each( function( field_element ) {
                            //auto-updates fields
                            field_element.down('div').update( field.value );
                        });
                    });
                });
            }

        })();

        (function enableRemainingEffortInPlaceEditing() {
            $$( '.valueOf_remaining_effort' ).each( function( remaining_effort_container ) {
                new tuleap.agiledashboard.cardwall.cardTextElementEditor( remaining_effort_container );
            })
        })();

        (function enableAssignedToInPlaceEditing() {
            $$( '.valueOf_assigned_to' ).each( function( assigned_to_container ) {
                new tuleap.agiledashboard.cardwall.cardSelectElementEditor( assigned_to_container );
            })
        })();

        $$('.cardwall_admin_ontop_mappings input[name^=custom_mapping]').each(function (input) {
            input.observe('click', function (evt) {
                if (this.checked) {
                    this.up('td').down('select').enable().focus().readonly = false;
                } else {
                    this.up('td').down('select').disable().readonly = true;
                }
            });
        });

    });

});
