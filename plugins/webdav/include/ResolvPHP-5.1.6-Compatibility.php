<?php
/**
 * This file is used only for php version 5.1.x or lower
 *
 * Attention! this class may give wrong results conserning Date and Time
 *
 */

if (!class_exists('DateTimeZone')) {
    /**
     * Rewriting for the DateTimeZone class
     *
     * @author ounish
     *
     */
    class DateTimeZone {

    }
}

if (!class_exists('DateTime')) {
    /**
     * Rewriting for the DateTime class
     *
     * @author ounish
     */
    class DateTime {

        public $date;
        const RFC1123 = 'D, d M Y H:i:s O';

        /**
         * Constuctor of the class
         *
         * @param date $date
         *
         * @return void
         */
        public function __construct($date) {

            $this->date = strtotime($date);

        }

        /**
         * This method is just to respect the original class
         *
         * @param String $timezone
         *
         * @return NULL
         */
        public function setTimeZone($timezone) {

            return;

        }

        /**
         * Returns date
         *
         * @return date
         */
        private function __getDate() {

            return date(DATE_ATOM, $this->date);

        }

        /**
         * Modifies the date format
         *
         * @param String $multiplier
         *
         * @return void
         */
        public function modify($multiplier) {

            $this->date = strtotime($this->__getDate() . ' ' . $multiplier);

        }

        /**
         * Returns the formated date
         *
         * @param String $format
         *
         * @return date
         */
        public function format($format) {

            return date($format, $this->date);

        }
    }
}

?>