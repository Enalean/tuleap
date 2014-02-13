/**
 * @license
 * =========================================================
 * bootstrap-datetimepicker.js
 * http://www.eyecon.ro/bootstrap-datepicker
 * =========================================================
 * Copyright 2012 Stefan Petre
 *
 * Contributions:
 *  - Andrew Rowls
 *  - Thiago de Arruda
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * =========================================================
 */

/**
 * Fixing Bootstrap Tooltip and PrototypeJS conflict
 * Details: http://stackoverflow.com/questions/15087129/popover-hides-parent-element-if-used-with-prototype-js
 */
!function($) {

    $.fn.datetimepicker.Constructor.prototype.hide = function() {
        // Ignore event if in the middle of a picker transition
        var collapse = this.widget.find('.collapse')
        for (var i = 0; i < collapse.length; i++) {
          var collapseData = collapse.eq(i).data('collapse');
          if (collapseData && collapseData.transitioning)
            return;
        }
        this.widget.hide();
        this.viewMode = this.startViewMode;
        this.showMode();
        this.set();

        this._detachDatePickerGlobalEvents();
    }

}(window.jQuery);
