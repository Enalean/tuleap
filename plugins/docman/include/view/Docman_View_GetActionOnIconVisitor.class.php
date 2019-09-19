<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_GetActionOnIconVisitor
*/


class Docman_View_GetActionOnIconVisitor /* implements Visitor*/
{

    function visitFolder(&$item, $params = array())
    {
        return $params['view']->getActionOnIconForFolder($item, $params);
    }

    function visitDocument($item, $params = array())
    {
        return 'show';
    }
    function visitWiki(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    function visitEmbeddedFile(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    function visitEmpty(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
}
