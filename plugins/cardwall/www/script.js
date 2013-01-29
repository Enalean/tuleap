Ajax.InPlaceMultiCollectionEditor = Class.create(Ajax.InPlaceCollectionEditor, {
    createEditField: function() {
        var list = new Element( 'select' );
        list.name = this.options.paramName;
        list.size = 1;

        if ( this.options.multiple ) {
            list.writeAttribute( 'multiple' );
            list.size = 2;
        }

        this._controls.editor = list;
        this._collection = this.options.collection || [];
        
        this.checkForExternalText();

        this._form.appendChild( this._controls.editor );

        if( jQuery && typeof jQuery( list ).select2 === 'function' ) {
            jQuery( list ).select2( { "width" : "250px" } );
        }
    },

    buildOptionList: function() {
        this._form.removeClassName( this.options.loadingClassName );
        this._controls.editor.update( '' );
        
        this.getSelectedUsers();
        this._collection.each( function( option ) {
            var option_element = new Element( 'option' ),
                option_key     = option[ 0 ],
                option_val     = option[ 1 ];

            option_element.value = option_key;
            option_element.selected = ( option_key in this.options.selected ) ? true : false;
            option_element.appendChild( document.createTextNode( option_val ) );

            this._controls.editor.appendChild( option_element );
        }.bind( this ));
        
        this._controls.editor.disabled = false;
        Field.scrollFreeActivate( this._controls.editor );
    },

    getSelectedUsers: function() {
        this.options.selected = {}
    }
});

document.observe('dom:loaded', function () {
    $$('.cardwall_board').each(function (board) {
        
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

        (function defineDroppableColumns(){
            var cols = board.select('col');

            cols.each(function (col) {
                col.up('table').down('tbody.cardwall').childElements().each(function (tr) {
                    var classname = 'drop-into-' + tr.id.split('-')[1] + '-' + col.id.split('-')[1];
                    tr.childElements()[col.up().childElements().indexOf(col)].select('.cardwall_board_postit').invoke('removeClassName', classname);
                });
            });

            cols.each(function (col, col_index) {
                col.up('table').down('tbody.cardwall').childElements().each(function (tr) {
                    var td           = tr.down('td.cardwall-cell', col_index),
                        restorecolor = td.getStyle('background-color'),
                        effect       = null,
                        swimline_id  = tr.id.split('-')[1],
                        accept       = 'drop-into-' + swimline_id + '-' + col.id.split('-')[1];
                    Droppables.add(td, {
                        hoverclass: 'cardwall_board_column_hover',
                        accept: accept,
                        onDrop: function (dragged, dropped, event) {
                            //change the classname of the post it to be accepted by the formers columns
                            dragged.addClassName('drop-into-' + swimline_id + '-' + cols[dragged.up('tr').childElements().indexOf(dragged.up('td'))].id.split('-')[1]);
                            dragged.removeClassName(accept);

                            //switch to the new column
                            Element.remove(dragged);
                            td.down('ul').appendChild(dragged);
                            dragged.setStyle({
                                left: 'auto',
                                top: 'auto'
                            });
                            if (effect) {
                                effect.cancel();
                            }
                            effect = new Effect.Highlight(td, {
                                restorecolor: restorecolor
                            });

                            //save the new state
                            var parameters = {
                                    aid: dragged.id.split('-')[1],
                                    func: 'artifact-update'
                                },
                                field_id,
                                value_id = col.id.split('-')[1];
                            if ($('tracker_report_cardwall_settings_column')) {
                                 field_id = $F('tracker_report_cardwall_settings_column');
                            } else {
                                field_id = dragged.readAttribute('data-column-field-id');
                                value_id = $F('cardwall_column_mapping_' + value_id + '_' + field_id);
                            }
                            parameters['artifact[' + field_id + ']'] = value_id;
                            var req = new Ajax.Request(codendi.tracker.base_url, {
                                method: 'POST',
                                parameters: parameters,
                                onComplete: function (response) {
                                    $H(response.responseJSON).each(function (card) {
                                        var card_element = $('cardwall_board_postit-' + card.key);
                                        if (card_element) {
                                            $H(card.value).each(function (field) {
                                                card_element.select('.valueOf_' + field.key).each(function (field_element) {
                                                    field_element.update(field.value);
                                                });
                                            });
                                        }
                                    });
                                    //TODO handle errors (perms, workflow, ...)
                                    // eg: change color of the post it
                                }
                            });
                        }
                    });
                });
            });
        })();
    });

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
