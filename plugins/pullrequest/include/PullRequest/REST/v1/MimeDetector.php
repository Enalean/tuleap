<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\Git\BinaryDetector;

class MimeDetector
{

    public static $EXTENSION_TO_MIME_TYPES = [
        'c'             => 'text/x-c',
        'cpp'           => 'text/x-c++src',
        'mm'            => 'text/x-c++src',
        'cc'            => 'text/x-c++src',
        'h'             => 'text/x-c++hdr',
        'cs'            => 'text/x-csharpr',
        'css'           => 'text/css',
        'coffee'        => 'text/x-coffeescript',
        'cl'            => 'text/x-common-lisp',
        'lisp'          => 'text/x-common-lisp',
        'diff'          => 'text/x-diff',
        'patch'         => 'text/x-diff',
        'edn'           => 'text/x-clojure',
        'erl'           => 'text/x-erlang',
        'feature'       => 'text/x-feature',
        'go'            => 'text/x-go',
        'groovy'        => 'text/x-groovy',
        'haml'          => 'text/x-haml',
        'hs'            => 'text/x-haskell',
        'hx'            => 'text/x-haxe',
        'htm'           => 'text/html',
        'html'          => 'text/html',
        'ini'           => 'text/x-ini',
        'java'          => 'text/x-java',
        'jl'            => 'text/x-julia',
        'js'            => 'text/javascript',
        'json'          => 'application/json',
        'jsx'           => 'text/jsx',
        'latex'         => 'text/x-stex',
        'tex'           => 'text/x-stex',
        'less'          => 'text/x-less',
        'behaviors'     => 'text/x-clojurescript',
        'keymap'        => 'text/x-clojurescript',
        'ls'            => 'text/x-livescript',
        'lua'           => 'text/x-lua',
        'md'            => 'text/x-markdown',
        'markdown'      => 'text/x-markdown',
        'sql'           => 'text/x-mysql',
        'ocaml'         => 'text/x-ocaml',
        'ml'            => 'text/x-ocaml',
        'pas'           => 'text/x-pascal',
        'pl'            => 'text/x-perl',
        'php'           => 'text/x-php',
        'txt'           => 'text/plain',
        'py'            => 'text/x-python',
        'pyw'           => 'text/x-python',
        'r'             => 'text/x-rsrc',
        'rb'            => 'text/x-ruby',
        'rs'            => 'text/x-rustsrc',
        'sass'          => 'text/x-sass',
        'scss'          => 'text/x-scss',
        'scala'         => 'text/x-scala',
        'ss'            => 'text/x-scheme',
        'scm'           => 'text/x-scheme',
        'sch'           => 'text/x-scheme',
        'sh'            => 'text/x-sh',
        'bash'          => 'text/x-sh',
        'profile'       => 'text/x-sh',
        'bash_profile'  => 'text/x-sh',
        'bashrc'        => 'text/x-sh',
        'zsh'           => 'text/x-sh',
        'zshrc'         => 'text/x-sh',
        'smarty'        => 'text/x-smarty',
        'sparql'        => 'text/x-sparql-query',
        'sql'           => 'text/x-sql',
        'swift'         => 'text/x-swift',
        'ts'            => 'text/x-typescript',
        'vb'            => 'text/x-vb',
        'xml'           => 'application/xml',
        'yml'           => 'text/x-yaml',
        'yaml'          => 'text/x-yaml'
    ];

    public static function getMimeInfo($file_path, $dest_content, $src_content)
    {
        $finfo = finfo_open(FILEINFO_MIME);
        $file_content = $src_content === null ? $dest_content : $src_content;
        $finfo_buffer = finfo_buffer($finfo, $file_content);
        if ($finfo_buffer != false) {
            $tokens    = explode(';', $finfo_buffer);
            $mime_type = $tokens[0];
            if (count($tokens) > 1) {
                $charset_info = $tokens[1];
            } else {
                $charset_info = '';
            }
        } else {
            $mime_type    = '';
            $charset_info = '';
        }
        finfo_close($finfo);

        if (substr($mime_type, 0, 5) === 'text/') {
            $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
            $mime_types = self::$EXTENSION_TO_MIME_TYPES;
            $mime_type = isset($mime_types[$file_ext]) ? $mime_types[$file_ext] : $mime_type;
        }

        $charset = str_replace(' charset=', '', $charset_info);
        if (BinaryDetector::isBinary($file_content)) {
            $charset = 'binary';
        }

        return [$mime_type, $charset];
    }
}
