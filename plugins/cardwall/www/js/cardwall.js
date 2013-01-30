/**
  * Copyright (c) Enalean, 2013. All rights reserved
  *
  * This file is a part of Tuleap.
  *
  * Tuleap is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Tuleap is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */
var tuleap = tuleap || { };
tuleap.agiledashboard = tuleap.agiledashboard || { };
tuleap.agiledashboard.cardwall = tuleap.agiledashboard.cardwall || { };
tuleap.agiledashboard.cardwall.card = tuleap.agiledashboard.cardwall.card || { };

tuleap.agiledashboard.cardwall.card.updateAfterAjax = function( transport ) {
    jQuery.each(transport.responseJSON, function( artifact_id, art_values ) {
        jQuery.each( art_values, function( field_name, field_value ) {
            jQuery( 'div[data-artifact-id='+ artifact_id +']' )
                .find( '.valueOf_' + field_name )
                .find( 'div' )
                .html( field_value );
        });
    })
};

tuleap.agiledashboard.cardwall.card.textElementEditor = Class.create({

    initialize : function( element, options ) {
        this.options = options || {};
        this.setProperties( element );

        if(! this.userCanEdit() ) {
            return;
        }

        this.injectTemporaryContainer();

        this.options['callback']      = this.ajaxCallback();
        this.options['onComplete']    = this.success();
        this.options['onFailure']     = this.fail;

        new Ajax.InPlaceEditor( this.div, this.update_url, this.options );
    },

    setProperties : function ( element ) {
        this.element        = element;
        this.field_id       = element.readAttribute( 'data-field-id' );
        this.artifact_id    = element.up( '.card' ).readAttribute( 'data-artifact-id' );
        this.update_url     = '/plugins/tracker/?func=artifact-update&aid=' + this.artifact_id;
        this.div            = new Element( 'div' );
        this.artifact_type  = element.readAttribute( 'data-field-type' );
    },

    injectTemporaryContainer : function () {
        this.accountForEmptyValues();
        this.div.update( this.element.innerHTML );
        this.element.update( this.div );
    },

    accountForEmptyValues : function() {
        if( this.element.innerHTML == '' ) {
            this.element.innerHTML = ' - ' ;
        }
    },

    ajaxCallback : function() {
        var field_id = this.field_id;

        return function setRequestData(form, value) {
            var parameters = {},
                linked_field = 'artifact[' + field_id +']';

            parameters[linked_field] = value;
            return parameters;
        }
    },

    success : function() {
        return function updateCardInfo(transport) {
            if( typeof transport != 'undefined' ) {
                tuleap.agiledashboard.cardwall.card.updateAfterAjax( transport );
            }
        }
    },

    fail : function(transport) {
        if( typeof transport === 'undefined' ) {
            return;
        }
        if( console && typeof console.error === 'function' ) {
            console.error( transport.responseText.stripTags() );
        }
    },

    userCanEdit : function() {
        return (this.field_id !== null)
    }
});

tuleap.agiledashboard.cardwall.card.selectElementEditor = Class.create({

    initialize : function( element, options ) {
        this.setProperties( element, options );
    
        if(! this.userCanEdit() ) {
            return;
        }

        this.checkUserData();
        this.checkMultipleSelect();
        this.addOptions();

        var container = this.createAndInjectTemporaryContainer();
        var editor;

        editor = new Ajax.InPlaceMultiCollectionEditor(
            container,
            this.update_url,
            this.options
        );

        this.bindSelectedElementsToEditor(editor);
    },


    userCanEdit : function() {
        return ( this.field_id !== null )
    },
    
    setProperties : function( element, options ) {
        this.element           = element;
        this.options           = options || {};
        this.tracker_user_data = [];

        this.field_id       = element.readAttribute( 'data-field-id' );
        this.artifact_id    = element.up( '.card' ).readAttribute( 'data-artifact-id' );
        this.artifact_type  = element.readAttribute( 'data-field-type' );

        this.update_url     = '/plugins/tracker/?func=artifact-update&aid=' + this.artifact_id;
        this.collection_url = '/plugins/tracker/?func=get-values&formElement=' + this.field_id;

        this.users          = {};
        this.multi_select   = false;
    },

    checkUserData : function() {
        if( typeof tuleap.agiledashboard.cardwall.tracker_user_data === 'undefined' ) {
            tuleap.agiledashboard.cardwall.tracker_user_data = [];
        }

        this.tracker_user_data = tuleap.agiledashboard.cardwall.tracker_user_data;
    },

    checkMultipleSelect : function () {
        this.multi_select = ( this.artifact_type === 'multiselectbox' );
    },

    addOptions : function() {
        this.options[ 'multiple' ]      = this.multi_select;
        this.options[ 'collection' ]    = this.getAvailableUsers();
        this.options[ 'element' ]       = this.element;
        this.options[ 'callback' ]      = this.preRequestCallback();
        this.options[ 'onComplete' ]    = this.success();
        this.options[ 'onFailure' ]     = this.fail;
    },

    bindSelectedElementsToEditor : function( editor ) {
        editor.getSelectedUsers = function() {
            var avatars = jQuery( '.cardwall_avatar', this.options.element );
            var users = {};

            avatars.each( function() {
                var id      = jQuery( this ).attr( 'data-user-id' );
                users[ id ] = jQuery( this ).attr( 'title' );
            });
            this.options.selected = users;
        };
    },

    createAndInjectTemporaryContainer : function () {
        var clickable     = this.getClickableArea(),
            clickable_div = new Element( 'div' );

        clickable_div.update( clickable );
        this.element.update( clickable_div );

        return clickable_div;
    },

    getClickableArea : function() {
        if( this.element.innerHTML == '' ) {
            return ' - ' ;
        }

        return this.element.innerHTML;
    },

    getAvailableUsers : function() {
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
    },

    fetchUsers : function() {
        var users = {};

        jQuery.ajax({
            url   : this.collection_url,
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

        tuleap.agiledashboard.cardwall.tracker_user_data[ this.field_id ] = users;
    },

    preRequestCallback : function() {
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
    },

    success : function() {
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

            function getNewValues(transport, is_multi_select, field_id) {
                var new_values;

                if ( is_multi_select ) {
                    new_values = transport.request.parameters[ 'artifact[' + field_id + '][]' ];
                } else {
                    new_values = transport.request.parameters[ 'artifact[' + field_id + ']' ];
                }

                return new_values;
            }

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
                    avatar_div.update( ' - ' );
                }
            }

            function generateAvatarDiv( user_id ) {
                var username = tracker_user_data[ field_id ][ user_id ][ 'username' ],
                    caption = tracker_user_data[ field_id ][ user_id ][ 'caption' ],
                    structure_div,
                    avatar_img,
                    avatar_div;

                structure_div = new Element( 'div' );

                avatar_div = new Element( 'div' );
                avatar_div.addClassName( 'cardwall_avatar' );
                avatar_div.writeAttribute( 'title', caption );
                avatar_div.writeAttribute( 'data-user-id', user_id );

                avatar_img = new Element('img')
                avatar_img.writeAttribute('src','/users/' + username + '/avatar.png');
                avatar_img.observe('load', function() {
                    if( this.width == 0 || this.height == 0 ) {
                        return;
                    }
                    avatar_div.appendChild(avatar_img);
                });

                structure_div.appendChild( avatar_div );
                structure_div.addClassName( 'avatar_structure_div' );

                return structure_div;
            }
        }
    },

    fail : function(transport) {
        if( typeof transport === 'undefined' ) {
            return;
        }
        if( console && typeof console.error === 'function' ) {
            console.error( transport.responseText.stripTags() );
        }
    }
});


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