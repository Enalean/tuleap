<?php // -*-php-*-
rcs_id('$Id PhpWeather.php 2002-08-26 15:30:13 rurban$');
/**
 Copyright 1999, 2000, 2001, 2002 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * This plugin requires a separate program called PhpWeather. For more
 * information and to download PhpWeather, see:
 *
 *   http://sourceforge.net/projects/phpweather/
 *
 * Usage:
 *
 * <?plugin PhpWeather ?>
 * <?plugin PhpWeather menu=true ?>
 * <?plugin PhpWeather icao=KJFK ?>
 * <?plugin PhpWeather language=en ?>
 * <?plugin PhpWeather units=only_metric ?>
 * <?plugin PhpWeather icao||=CYYZ cc||=CA language||=en menu=true ?>
 *
 * If you want a menu, and you also want to change the default station
 * or language, then you have to use the ||= form, or else the user
 * wont be able to change the station or language.
 *
 * The units argument should be one of only_metric, only_imperial,
 * both_metric, or both_imperial.
 */

// We require the base class from PHP Weather. Try some default directories. 
// Better define PHPWEATHER_BASE_DIR to the directory on your server:
if (!defined('PHPWEATHER_BASE_DIR')) {
    /* PhpWeather has not been loaded before. We include the base
     * class from PhpWeather, adjust this to match the location of
     * PhpWeather on your server: */
    if (!isset($_SERVER))
        $_SERVER =& $GLOBALS['HTTP_SERVER_VARS'];
    @include_once($_SERVER['DOCUMENT_ROOT'] . '/phpweather-2.2.1/phpweather.php');
    if (!defined('PHPWEATHER_BASE_DIR'))
        @include_once($_SERVER['DOCUMENT_ROOT'] . '/phpweather/phpweather.php');
}

class WikiPlugin_PhpWeather
extends WikiPlugin
{
    function getName () {
        return _("PhpWeather");
    }

    function getDescription () {
        return _("The PhpWeather plugin provides weather reports from the Internet.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.12 $");
    }

    function getDefaultArguments() {
        global $LANG;
        return array('icao'  => 'EKAH',
                     'cc'    => 'DK',
                     'language'  => 'en',
                     'menu'  => false,
                     'units' => 'both_metric');
    }

    function run($dbi, $argstr, &$request, $basepage) {
        // When 'phpweather/phpweather.php' is not installed then
        // PHPWEATHER_BASE_DIR will be undefined.
        if (!defined('PHPWEATHER_BASE_DIR'))
            return $this->error(_("You have to define PHPWEATHER_BASE_DIR before use. (config/config.ini)")); //early return

        require_once(PHPWEATHER_BASE_DIR . '/output/pw_images.php');
        require_once(PHPWEATHER_BASE_DIR . '/pw_utilities.php');

        extract($this->getArgs($argstr, $request));
        $html = HTML();

        $w = new phpweather(); // Our weather object

        if (!empty($icao)) {
            /* We assign the ICAO to the weather object: */
            $w->set_icao($icao);
            if (!$w->get_country_code()) {
                /* The country code couldn't be resolved, so we
                 * shouldn't use the ICAO: */
                trigger_error(sprintf(_("The ICAO '%s' wasn't recognized."),
                                      $icao), E_USER_NOTICE);
                $icao = '';
            }
        }

        if (!empty($icao)) {

            /* We check and correct the language if necessary: */
            //if (!in_array($language, array_keys($w->get_languages('text')))) {
            if (!in_array($language, array_keys(get_languages('text')))) {
                trigger_error(sprintf(_("%s does not know about the language '%s', using 'en' instead."),
                                      $this->getName(), $language),
                              E_USER_NOTICE);
                $language = 'en';
            }

            $class = "pw_text_$language";
            require_once(PHPWEATHER_BASE_DIR . "/output/$class.php");

            $t = new $class($w);
            $t->set_pref_units($units);
            $i = new pw_images($w);

            $i_temp = HTML::img(array('src' => $i->get_temp_image()));
            $i_wind = HTML::img(array('src' => $i->get_winddir_image()));
            $i_sky  = HTML::img(array('src' => $i->get_sky_image()));

            $m = $t->print_pretty();

            $m_td = HTML::td(HTML::p(new RawXml($m)));

            $i_tr = HTML::tr();
            $i_tr->pushContent(HTML::td($i_temp));
            $i_tr->pushContent(HTML::td($i_wind));

            $i_table = HTML::table($i_tr);
            $i_table->pushContent(HTML::tr(HTML::td(array('colspan' => '2'),
                                                    $i_sky)));

            $tr = HTML::tr();
            $tr->pushContent($m_td);
            $tr->pushContent(HTML::td($i_table));

            $html->pushContent(HTML::table($tr));

        }

        /* We make a menu if asked to, or if $icao is empty: */
        if ($menu || empty($icao)) {

            $form_arg = array('action' => $request->getURLtoSelf(),
                              'method' => 'get');

            /* The country box is always part of the menu: */
            $p1 = HTML::p(new RawXml(get_countries_select($w, $cc)));

            /* We want to save the language: */
            $p1->pushContent(HTML::input(array('type'  => 'hidden',
                                               'name'  => 'language',
                                               'value' => $language)));
            /* And also the ICAO: */
            $p1->pushContent(HTML::input(array('type'  => 'hidden',
                                               'name'  => 'icao',
                                               'value' => $icao)));

            $caption = (empty($cc) ? _("Submit country") : _("Change country"));
            $p1->pushContent(HTML::input(array('type'  => 'submit',
                                               'value' => $caption)));

            $html->pushContent(HTML::form($form_arg, $p1));

            if (!empty($cc)) {
                /* We have selected a country, now display a list with
                 * the available stations in that country: */
                $p2 = HTML::p();

                /* We need the country code after the form is submitted: */
                $p2->pushContent(HTML::input(array('type'  => 'hidden',
                                                   'name'  => 'cc',
                                                   'value' => $cc)));

                $p2->pushContent(new RawXml(get_stations_select($w, $cc, $icao)));
                $p2->pushContent(new RawXml(get_languages_select($language)));
                $p2->pushContent(HTML::input(array('type'  => 'submit',
                                                   'value' => _("Submit location"))));

                $html->pushContent(HTML::form($form_arg, $p2));

            }

        }

        return $html;
    }
};

// $Log: PhpWeather.php,v $
// Revision 1.12  2004/06/01 16:46:42  rurban
// Better error message. Search in /phpweather-2.2.1 and in /phpweather.
//
// Revision 1.11  2004/05/01 15:59:29  rurban
// more php-4.0.6 compatibility: superglobals
//
// Revision 1.10  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.9  2003/01/28 21:10:38  zorloc
// Better error messages from PhpWeather Plugin -- Martin Geisler
//
// Revision 1.8  2003/01/18 22:01:43  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
//
// Revision 1.7  2002/12/31 20:53:40  carstenklapp
// Bugfixes: Fixed menu language selection (incorrect parameters to
// $w->get_languages_select() & form input 'language' instead of 'lang').

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
