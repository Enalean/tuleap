/* ===========================================================
 * http://getbootstrap.com/2.3.2/javascript.html#tooltips
 * Inspired by the original jQuery.tipsy by Jason Frame
 * ===========================================================
 * Copyright 2013 Twitter, Inc.
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
 * ========================================================== */

/**
 * Fixing Bootstrap Tooltip and PrototypeJS conflict
 * Details: http://stackoverflow.com/questions/15087129/popover-hides-parent-element-if-used-with-prototype-js
 */
!function($) {
    $.fn.tooltip.Constructor.prototype.hide = function () {
        var that = this
        , $tip = this.tip()
        , e = $.Event('hide')

        if (e.isDefaultPrevented()) return

        $tip.removeClass('in')

        function removeWithAnimation() {
            var timeout = setTimeout(function () {
                $tip.off($.support.transition.end).detach()
            }, 500)

            $tip.one($.support.transition.end, function () {
                clearTimeout(timeout)
                $tip.detach()
            })
        }

        $.support.transition && this.$tip.hasClass('fade') ?
            removeWithAnimation() :
            $tip.detach()

        this.$element.trigger('hidden')

        return this
    }
}(window.jQuery);