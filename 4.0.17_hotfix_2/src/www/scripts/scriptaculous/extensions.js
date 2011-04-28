/*
 * InPlaceEditor extension that adds a 'click to edit' text when the field is 
 * empty.
 */
Ajax.InPlaceEditor.prototype.__initialize = Ajax.InPlaceEditor.prototype.initialize;
Ajax.InPlaceEditor.prototype.__getText = Ajax.InPlaceEditor.prototype.getText;
Ajax.InPlaceEditor.prototype.__onComplete = Ajax.InPlaceEditor.prototype.onComplete;
Ajax.InPlaceEditor.prototype = Object.extend(Ajax.InPlaceEditor.prototype, {

    initialize: function(element, url, options){
        this.__initialize(element,url,options)
        this.setOptions(options);
        this._checkEmpty();
    },

    setOptions: function(options){
        this.options = Object.extend(Object.extend(this.options,{
            emptyText: 'click to edit...',
            emptyClassName: 'inplaceeditor-empty'
        }),options||{});
    },

    _checkEmpty: function(){
        if( this.element.innerHTML.length == 0 ){
            this.element.appendChild(
                Builder.node('span',{className:this.options.emptyClassName},this.options.emptyText));
        }
    },

    getText: function(){
        document.getElementsByClassName(this.options.emptyClassName,this.element).each(function(child){
            this.element.removeChild(child);
        }.bind(this));
        return this.__getText();
    },

    onComplete: function(transport){
        this._checkEmpty();
        this.__onComplete(transport);
    }
});
