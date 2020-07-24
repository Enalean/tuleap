<?php
// -*-php-*-
rcs_id('$Id: Utils.php,v 1.2 2004/11/15 16:00:02 rurban Exp $');
/*
 Copyright 2004 Mike Cassano

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


function addPageTextData($user, $dbi, $new_data, $START_DELIM, $DELIM)
{
    // This is largely lifted from the TranslateText plugin, which performs a
    // similar set of functions (retrieves a user's homepage, modifies it
    // progmatically, and saves the changes)
    $homepage = $user->_HomePagehandle;
    $transpagename = $homepage->getName();
    $page    = $dbi->getPage($transpagename);
    $current = $page->getCurrentRevision();
    $version = $current->getVersion();
    if ($version) {
        $text = $current->getPackedContent() . "\n";
        $meta = $current->_data;
    } else {
        $text = '';
        $meta = ['markup' => 2.0,
                      'author' => $user->getId()];
    }

    // add new data to the appropriate line
    if (preg_match('/^' . preg_quote($START_DELIM, '/') . '/', $text)) {
        // need multiline modifier to match EOL correctly
        $text = preg_replace(
            '/(^' . preg_quote($START_DELIM, '/') . '.*)$/m',
            '$1' . $DELIM . $new_data,
            $text
        );
    } else {
        // handle case where the line does not yet exist
        $text .= "\n" . $START_DELIM . $new_data . "\n";
    }

    // advance version counter, save
    $page->save($text, $version + 1, $meta);
}

function getMembers($groupName, $dbi, $START_DELIM = false)
{
    if (! $START_DELIM) {
        $START_DELIM = _("Members:");
    }
    return getPageTextData($groupName, $dbi, $START_DELIM);
}

function getPageTextData($fromUser, $dbi, $START_DELIM)
{
    if (is_object($fromUser)) {
        $fromUser = $fromUser->getId();
    }
    if ($fromUser == "") {
        return "";
    }
    $userPage = $dbi->getPage($fromUser);
    $transformed = $userPage->getCurrentRevision();
    $pageArray = $transformed->getContent();
    $p = -1;
    for ($i = 0; $i < count($pageArray); $i++) {
        if ($pageArray[$i] != "") {
            if (! ((strpos($pageArray[$i], $START_DELIM)) === false)) {
                $p = $i;
                break;
            }
        }
    }
    $retArray = [];
    if ($p >= 0) {
        $singles = $pageArray[$p];
        $singles = substr($singles, strpos($singles, $START_DELIM) + strlen($START_DELIM));

        $retArray = explode(',', $singles);
    }
    for ($i = 0; $i < count($retArray); $i++) {
        $retArray[$i] = trim($retArray[$i]);
    }
    //$retArray = array_filter($retArray, "notEmptyName");
    $retArray = array_unique($retArray);

    return $retArray;
}

function notEmptyName($var)
{
    return $var != "";
}

// $Log: Utils.php,v $
// Revision 1.2  2004/11/15 16:00:02  rurban
// enable RateIt imgPrefix: '' or 'Star' or 'BStar',
// enable blue prediction icons,
// enable buddy predictions.
//
// Revision 1.1  2004/06/18 14:42:17  rurban
// added wikilens libs (not yet merged good enough, some work for DanFr)
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
