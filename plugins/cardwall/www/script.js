Ajax.InPlaceCollectionEditorMulti = Class.create(Ajax.InPlaceCollectionEditor, {
    createEditField: function() {
        var list = new Element( 'select' );
        list.name = this.options.paramName;
        list.size = 1;

        if (this.options.multiple) {
            list.writeAttribute('multiple');
            list.size = 2;
        }

        this._controls.editor = list;
        this._collection = this.options.collection || [];
        
        this.checkForExternalText();

        this._form.appendChild(this._controls.editor);
        jQuery( list ).select2();
    },

    buildOptionList: function() {
        var marker,
            textFound;

        this.getSelectedUsers();
        this._form.removeClassName(this.options.loadingClassName);
        this._collection = this._collection.map(function(entry) {
            return 2 === entry.length ? entry : [entry, entry].flatten();
        });

        marker = ('value' in this.options) ? this.options.value : this._text;
        textFound = this._collection.any(function(entry) {
            return entry[0] == marker;
        }.bind(this));

        this._controls.editor.update('');
        
        this._collection.each(function(entry, index) {
            var option;

            option = new Element( 'option' );
            option.value = entry[0];
            if (this.options.selected) {    
                option.selected = (entry[0] in this.options.selected) ? 1 : 0;
            } else {
                option.selected = textFound ? entry[0] == marker : 0 == index;
            }
            option.appendChild(document.createTextNode(entry[1]));
            this._controls.editor.appendChild(option);
        }.bind(this));
        
        this._controls.editor.disabled = false;
        Field.scrollFreeActivate(this._controls.editor);
    },

    getSelectedUsers: function() {}
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

    function cardTextElementEditor ( element ) {
        this.element        = element;
        this.field_id       = element.readAttribute( 'data-field-id' );
        this.artifact_id    = element.up( '.card' ).readAttribute( 'data-artifact-id' );
        this.url            = '/plugins/tracker/?func=artifact-update&aid=' + this.artifact_id;
        this.div            = new Element( 'div' );
        this.artifact_type  = element.readAttribute( 'data-field-type' );

        this.init = function() {
            this.injectTemporaryContainer();

            new Ajax.InPlaceEditor( this.div, this.url, {
                formClassName : 'card_element_edit_form',
                callback   : this.ajaxCallback(),
                onComplete : this.success(),
                onFailure  : this.fail
            });
        };

        this.injectTemporaryContainer = function () {
            this.accountForEmptyValues();
            this.div.update( this.element.innerHTML );
            this.element.update( this.div );
        };

        this.accountForEmptyValues = function() {
            if( this.element.innerHTML == '' ) {
                this.element.innerHTML = '0' ;
            }
        }

        this.ajaxCallback = function() {
            var field_id = this.field_id;

            return function setRequestData(form, value) {
                var parameters = {},
                    linked_field = 'artifact[' + field_id +']';

                parameters[linked_field] = value;
                return parameters;
            }
        };

        this.success = function() {
            var field_id    = this.field_id;
            var div         = this.div;

            return function updateCardInfo(transport) {
                if( typeof transport != 'undefined' ) {
                    div.update(transport.request.parameters[ 'artifact[' + field_id + ']' ]);
                }
            }
        };

        this.fail = function() {
        };

        this.init();
    }

    /**
     * @var element element DOM element
     * @var object  options List of options to pass to InPlaceCollectionEditorMulti instance
     * @var array   tracker_user_data Array in which to store allowed users per artifact ID.
     *                                Avoids multple ajax calls for same data
     */
    function cardSelectElementEditor ( element, options, tracker_user_data ) {
        this.element           = element;
        this.options           = options;
        this.tracker_user_data = tracker_user_data;

        this.field_id       = element.readAttribute( 'data-field-id' );
        this.artifact_id    = element.up( '.card' ).readAttribute( 'data-artifact-id' );
        this.artifact_type  = element.readAttribute( 'data-field-type' );

        this.url            = '/plugins/tracker/?func=artifact-update&aid=' + this.artifact_id;
        this.collectionUrl  = '/plugins/tracker/?func=get-values&formElement=' + this.field_id;

        this.users          = {};
        this.multi_select   = false;

        this.init = function() {
            var container = this.createAndInjectTemporaryContainer();
            var editor;

            this.checkMultipleSelect();
            this.addOptions();

            editor = new Ajax.InPlaceCollectionEditorMulti(
                container,
                this.url,
                this.options
            );
                
            this.bindSelectedElementsToEditor(editor);
        };

        this.bindSelectedElementsToEditor = function( editor ) {

            editor.getSelectedUsers = function() {
                var avatars = jQuery( '.cardwall_avatar', this.options.element );
                var users = {};

                avatars.each( function() {
                    var id      = jQuery( this ).attr( 'data-user-id' );
                    users[ id ] = jQuery( this ).attr( 'title' );
                });

                this.options.selected = users;
            };
        }
        

        this.createAndInjectTemporaryContainer = function () {
            var clickable     = this.getClickableArea(),
                clickable_div = new Element( 'div' );

            clickable_div.update( clickable );
            this.element.update( clickable_div );

            return clickable_div;
        };

        this.checkMultipleSelect = function () {
            this.multi_select = ( this.artifact_type === 'multiselectbox' );
        };

        this.addOptions = function() {
            this.options[ 'multiple' ]      = this.multi_select;
            this.options[ 'formClassName' ] = 'card_element_edit_form';
            this.options[ 'collection' ]    = this.getAvailableUsers();
            this.options[ 'element' ]       = this.element;
            this.options[ 'callback' ]      = this.preRequestCallback();
            this.options[ 'onComplete' ]    = this.success();
            this.options[ 'onFailure' ]     = this.fail;
        };

        this.getClickableArea = function() {
            if( this.element.innerHTML == '' ) {
                return '?' ;
            }
            
            return this.element.innerHTML;
        }

        this.getAvailableUsers = function() {
            var user_collection = [];

            if ( Object.keys( this.users ).length == 0 ) {
                this.users = this.tracker_user_data[ this.field_id ] || [];
            }

            if ( Object.keys(this.users).length == 0 ) {
                this.fetchUsers();
            }

            jQuery.each( this.users, function( id, user_details ){
                if( typeof( user_details ) !== 'undefined' ) {
                    user_collection.push( [ user_details.id, user_details.caption ] );
                }
            });

            return user_collection;
        };

        this.fetchUsers = function() {
            var users = {};

            jQuery.ajax({
                url   : this.collectionUrl,
                async : false
            }).done(function ( data ) {
                jQuery.each(data, function( id, user_details ) {
                    users[ id ] = user_details;
                });
            }).fail( function() {
                users = {};
            });

            this.users = users;
            this.tracker_user_data[ this.field_id ] = users;
        };

        this.preRequestCallback = function() {
            var field_id        = this.field_id,
                is_multi_select = (this.multi_select === true);

            return function setRequestData( form, value ) {
                var parameters = {};

                if ( is_multi_select ) {
                    linked_field = 'artifact[' + field_id +'][]';
                } else {
                    linked_field = 'artifact[' + field_id +']';
                }

                parameters[ linked_field ] = value;
                return parameters;
            }
        };

        this.success = function() {
            var field_id          = this.field_id,
                is_multi_select   = (this.multi_select === true),
                tracker_user_data = this.tracker_user_data;
                
            return function updateCardInfo( transport, element ) {
                var new_values;

                if( typeof transport === 'undefined' ) {
                    return;
                }

                element.update( '' );
                new_values = getNewValues( transport, is_multi_select, field_id );
                updateAvatarDiv( element, new_values );

                function updateAvatarDiv( avatar_div, new_values ) {
                    var div_html;

                    if(new_values instanceof Array) {
                        for(var i=0; i<new_values.length; i++) {
                            div_html = generateAvatarDiv( new_values[i] );
                            avatar_div.appendChild( div_html );
                        }
                    } else if( typeof new_values === 'string' ){
                        div_html = generateAvatarDiv( new_values );
                        avatar_div.appendChild( div_html );
                    } else {
                        avatar_div.update( '?' );
                    }
                }
                
                function getNewValues(transport, is_multi_select, field_id) {
                    var new_values;

                    if ( is_multi_select ) {
                        new_values = transport.request.parameters[ 'artifact[' + field_id + '][]' ];
                    } else {
                        new_values = transport.request.parameters[ 'artifact[' + field_id + ']' ];
                    }

                    return new_values;
                }

                function generateAvatarDiv( user_id ) {
                    var username = tracker_user_data[ field_id ][ user_id ][ 'username' ],
                        caption = tracker_user_data[ field_id ][ user_id ][ 'caption' ],
                        structure_div,
                        avatar_img,
                        avatar_div;

                    structure_div = new Element( 'div' );

                    avatar_img = new Element( 'img' );
                    avatar_img.writeAttribute('src', '/users/' + username + '/avatar.png');

                    avatar_div = new Element( 'div' );
                    avatar_div.addClassName( 'cardwall_avatar' );
                    avatar_div.writeAttribute( 'title', caption );
                    avatar_div.writeAttribute( 'data-user-id', user_id );
                    avatar_div.appendChild( avatar_img );

                    structure_div.appendChild( avatar_div );
                    structure_div.addClassName( 'avatar_structure_div' );

                    return structure_div;
                }
            }
        };

        this.fail = function(transport) {
            if( typeof transport === 'undefined' ) {
                return;
            }
            if( console && typeof console.error === 'function' ) {
                console.error( transport.responseText.stripTags() );
            }
        };

        this.init();
    }

    (function enableRemainingEffortInPlaceEditing() {
        $$( '.valueOf_remaining_effort' ).each( function( remaining_effort_container ) {
            new cardTextElementEditor( remaining_effort_container );
        })
    })();

    (function enableAssignedToInPlaceEditing() {
        var tracker_user_data = [];
        $$( '.valueOf_assigned_to' ).each( function( assigned_to_container ) {
            var options = {};
            new cardSelectElementEditor( assigned_to_container, options, tracker_user_data );
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
