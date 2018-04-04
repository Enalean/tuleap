<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Http;

class BinaryFileResponse
{
    /**
     * @var string
     */
    private $file_path;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $content_type;

    public function __construct($file_path, $name = '', $content_type = 'application/octet-stream')
    {
        if (! is_readable($file_path)) {
            throw new \RuntimeException("$file_path is not readable");
        }
        $this->file_path    = $file_path;
        $this->name         = $name;
        $this->content_type = $content_type;
    }

    public function send()
    {
        header("Content-Type: $this->content_type");
        header('Content-Disposition: attachment; filename="' . $this->getName() . '"');
        header('Content-Length: ' . filesize($this->file_path));
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; form-action 'none';");
        header('X-DNS-Prefetch-Control: off');

        if (ob_get_level()) {
            ob_end_clean();
        }
        flush();
        $file = fopen($this->file_path, "r");
        while (! feof($file)) {
            print fread($file, 30*1024);
            flush();
        }
        fclose($file);
        exit();
    }

    /**
     * @return string
     */
    private function getName()
    {
        $name = $this->name;
        if ($name === '') {
            $name = basename($this->file_path);
        }

        return str_replace('"', '\\"', $this->removeNonPrintableASCIIChars($name));
    }

    /**
     * @return string
     */
    private function removeNonPrintableASCIIChars($str)
    {
        return preg_replace('/[^(\x20-\x7F)]*/', '', $str);
    }
}
