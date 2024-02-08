<?php
//=======================================================================
// File:        JPGRAPH.PHP
// Description: PHP Graph Plotting library. Base module.
// Created:     2001-01-08
// Ver:         $Id: jpgraph.php 1924 2010-01-11 14:03:26Z ljp $
//
// Copyright (c) Asial Corporation. All rights reserved.
//========================================================================

// Useful mathematical function
function sign($a)
{
    return $a >= 0 ? 1 : -1;
}

// Utility function to generate an image name based on the filename we
// are running from and assuming we use auto detection of graphic format
// (top level), i.e it is safe to call this function
// from a script that uses JpGraph
function GenImgName()
{
    // Determine what format we should use when we save the images
    $supported = imagetypes();
    if ($supported & IMG_PNG) {
        $img_format = 'png';
    } elseif ($supported & IMG_GIF) {
        $img_format = 'gif';
    } elseif ($supported & IMG_JPG) {
        $img_format = 'jpeg';
    } elseif ($supported & IMG_WBMP) {
        $img_format = 'wbmp';
    } elseif ($supported & IMG_XPM) {
        $img_format = 'xpm';
    }

    if (! isset($_SERVER['PHP_SELF'])) {
        JpGraphError::RaiseL(25005);
        //(" Can't access PHP_SELF, PHP global variable. You can't run PHP from command line if you want to use the 'auto' naming of cache or image files.");
    }
    $fname = basename($_SERVER['PHP_SELF']);
    if (! empty($_SERVER['QUERY_STRING'])) {
        $q      = @$_SERVER['QUERY_STRING'];
        $fname .= '_' . preg_replace('/\W/', '_', $q) . '.' . $img_format;
    } else {
        $fname = substr($fname, 0, strlen($fname) - 4) . '.' . $img_format;
    }
    return $fname;
}
