<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Handle toggler functionnality
 *
 * A toggler is made of a dom element which toggle
 * the display of his siblings when the user click on it.
 * As it is pretty generic. The aim of this class is to provide
 * help on determining the classname of the element and handle
 * ajax calls to save the state of the display in
 * user preferences.
 *
 * If the user is anonymous then no ajax call will be made or handled.
 *
 * The methods here are based on a $id parameter. It is important
 * for Ajax calls that the id given is the same as the id of the
 * dom element used as toggler.
 *
 * Example:
 * <div>
 *   <h3 class="toggler" id="meaning">The meaning of life</h3>
 *   <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 *      Quisque mi. Curabitur turpis mauris, malesuada tristique,
 *      molestie nec, pulvinar eget, ipsum. Maecenas varius pede
 *      id eros. Quisque convallis.</p>
 *   <p>Cum sociis natoque penatibus et magnis dis parturient montes,
 *      nascetur ridiculus mus. Sed lorem justo, faucibus id,
 *      tincidunt eu, consequat id, mi. Nunc euismod pede sit
 *      amet leo. Praesent bibendum libero sit amet sapien.</p>
 * </div>
 * => If the user click on the title "The meaning of life"
 *    Then all siblings will be hide/shown and an ajax call
 *    will be made to save the state of the id "meaning"
 *
 * Classnames used:
 * - toggler: the siblings are initially shown.
 *            An ajax call will be made if the h3 has an id
 * - toggler-hide: the siblings are initially hidden.
 *                 An ajax call will be made if the h3 has an id
 * - toggler-noajax: the siblings are initially hidden. No ajax call.
 * - toggler-hide: the siblings are initially hidden. No ajax call.
 *
 * For now the toggler is simple. The sibblings must be present
 * in the dom as no ajax call will be made to fetch the content
 * if it is initially hidden. This allows us to support no-javascript
 * browsers.
 *
 * For some togglers, the state must be saved in other place than the
 * user preferences. In that case, it is up to the service to handle
 * the save.
 */
class Toggler
{

    /**
     *
     * @param string $id the id of the toggler
     * @param bool $force optionnal paremeter. Set it to true or false if you want to force show or hide
     * @param bool $noajax optinnal parameter. Set it to true if you don't want ajax for registered users
     *
     * @return string the classname of the toggler depending on the current state
     */
    public static function getClassname($id, $force = null, $noajax = false)
    {
        $current_user = UserManager::instance()->getCurrentUser();
        $ajax_mode = $current_user->isAnonymous() || $noajax ? '-noajax' : '';
        if ($current_user->isAnonymous()) {
            return $force === true ? 'toggler' . $ajax_mode : 'toggler-hide' . $ajax_mode;
        } else {
            if ($force === null) {
                return $current_user->getPreference('toggle_' . $id) ? 'toggler' . $ajax_mode : 'toggler-hide' . $ajax_mode;
            } else {
                return $force ? 'toggler' . $ajax_mode : 'toggler-hide' . $ajax_mode;
            }
        }
    }

    /**
     * Save the state of the toggler
     *
     * @param string $id the id of the toggler
     */
    public static function toggle($id)
    {
        $current_user = UserManager::instance()->getCurrentUser();
        if ($current_user->isLoggedIn()) {
            $done = false;
            EventManager::instance()->processEvent(Event::TOGGLE, array('id' => $id, 'user' => $current_user, 'done' => &$done));
            if (!$done) {
                if (strpos($id, 'tracker_report_query_') === 0) {
                    $report_id = (int) substr($id, strlen('tracker_report_query_'));
                    $report_factory = ArtifactReportFactory::instance();
                    if (($report = $report_factory->getReportById($report_id, $current_user->getid())) && $report->userCanUpdate($current_user)) {
                        $report->toggleQueryDisplay();
                        $report_factory->save($report);
                    }
                } else {
                    self::togglePreference($current_user, $id);
                }
            }
        }
    }

    /**
     * Returns true if the toggler should be displayed
     *
     * @param PFUser   $user    The user
     * @param string $id      the id of the toggler
     * @param bool   $default if we don't know, return $default
     *
     * @return bool
     */
    public static function shouldBeDisplayed(PFUser $user, $id, $default)
    {
        if ($user->isLoggedIn()) {
            $should_be_displayed = $user->getPreference('toggle_' . $id); //TODO: DRY 'toggle_'. $id
            if ($should_be_displayed !== false) {
                return $should_be_displayed;
            }
        }
        return $default;
    }

    /**
     * Toggle the preference.
     * Should not be called directly unless you know what you do
     *
     * @param PFUser   $current_user The user
     * @param string $id           the id of the toggler
     */
    public static function togglePreference(PFUser $current_user, $id)
    {
        $current_user->setPreference('toggle_' . $id, 1 - (int) $current_user->getPreference('toggle_' . $id));
    }
}
