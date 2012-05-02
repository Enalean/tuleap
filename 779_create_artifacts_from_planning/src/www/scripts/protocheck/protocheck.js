/*--------------------------------------------------------------------------
| Proto Check, version 1.0
| (c) 2008 Neil Harrison
| Released under the terms and conditions of the
| Creative Commons Attribution-Share Alike
| http://creativecommons.org/licenses/by-sa/3.0/
*--------------------------------------------------------------------------*/
ProtoCheck = Class.create({
   initialize: function(options) {
      this.options = {
         checkClass:             'pc_checkbox',
         radioClass:             'pc_radiobutton',
         checkOnClass:           'pc_check_checked',
         checkOffClass:          'pc_check_unchecked',
         radioOnClass:           'pc_radio_checked',
         radioOffClass:          'pc_radio_unchecked',
         checkOnDisabledClass:   'pc_check_checked_disabled',
         checkOffDisabledClass:  'pc_check_unchecked_disabled',
         radioOnDisabledClass:   'pc_radio_checked_disabled',
         radioOffDisabledClass:  'pc_radio_unchecked_disabled',
         focusClass:             'pc_focus'
      };
      Object.extend(this.options, options || { });
      this.classez               = [];
      this.disClassez            = [];
      this.classez.checkbox      = {"on": this.options.checkOnClass, "off": this.options.checkOffClass};
      this.disClassez.checkbox   = {"on": this.options.checkOnDisabledClass, "off": this.options.checkOffDisabledClass};
      this.classez.radio         = {"on": this.options.radioOnClass, "off": this.options.radioOffClass};
      this.disClassez.radio      = {"on": this.options.radioOnDisabledClass, "off": this.options.radioOffDisabledClass};
      var elements = $$("label."+this.options.checkClass).concat($$("label."+this.options.radioClass));
      elements.each(function(label) {
         var element = label.down();
         element.setStyle({position:'absolute',left:'-9999px'});
         if (element.checked) {
            this.check(element, label);
         } else {
            this.uncheck(element, label);
         }
         if (!element.disabled) {
            element.observe("click", function(ev) {
               this.click(ev);
            }.bind(this));
            element.observe("focus", function(ev) {
               this.focus(ev);
            }.bind(this));
            element.observe("blur", function(ev) {
               this.blur(ev);
            }.bind(this));
            if (this.fixIE) {
               label.observe("click", function(ev) {
                  this.clickIE6(ev);
               }.bind(this));
            }
         }
      }.bind(this));
   },
   fixIE: (function(agent){
      var version = new RegExp('MSIE ([\\d.]+)').exec(agent);
      return version ? (parseFloat(version[1]) <= 6) : false;
   })(navigator.userAgent),
   check: function(element, label) {
      var css = element.disabled ? this.disClassez[element.type] : this.classez[element.type];
      label.addClassName(css.on).removeClassName(css.off);
   },
   uncheck: function(element, label) {
      var css = element.disabled ? this.disClassez[element.type] : this.classez[element.type];
      label.addClassName(css.off).removeClassName(css.on);
   },
   focus: function(ev) {
      var label = ev.element().up();
      label.addClassName(this.options.focusClass);
   },
   blur: function(ev) {
      var label = ev.element().up();
      label.removeClassName(this.options.focusClass);
   },
   click: function(ev) {
      var element = ev.element();
      var label = element.up();
      this.update(ev, element, label);
   },
   clickIE6: function(ev) {
      var label = ev.element();
      if (label.nodeName == "LABEL") {
         var element = label.down();
         element.click();
      }
   },
   update: function(ev, element, label) {
      if (label.hasClassName(this.options.checkClass)) {
         if (element.checked) {
            this.check(element, label);
         } else {
            this.uncheck(element, label);
         }
      }
      if (label.hasClassName(this.options.radioClass)) {
         $$("input[name="+element.name+"]").each(function(but) {
            if (element != but) {
               this.uncheck(but, but.up());
            }
         }.bind(this));
         this.check(element, label);
      }
      element.focus();
   }
});
