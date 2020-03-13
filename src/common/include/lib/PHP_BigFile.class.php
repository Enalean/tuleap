<?php
/**
 * Nicolas Terray
 *
 * PHP_BigFile is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * PHP_BigFile is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PHP_BigFile. If not, see <http://www.gnu.org/licenses/>.
 *
 * The original version might be found on:
 * https://bitbucket.org/vaceletm/php_bigfile
 */

class PHP_BigFile
{

    /**
     * @var resource The current context, or NULL if no context was passed to the caller function
     */
    public $context;

    /**
     * @var string The path to the file
     */
    protected $path;

    /**
     * @var int The current offset
     */
    protected $offset = 0;

    /**
     * @var int The size of the file
     */
    protected $filesize = 0;

    /**
     * @var string the name of the protocol
     */
    public const PROTOCOL = 'php-bigfile';

    /**
     * @return string the stream url to use with fopen & co
     */
    public static function stream($file)
    {
        return self::PROTOCOL . '://' . $file;
    }

    /**
     * Register the wrapper
     *
     * @throw RuntimeException if we cannot register the protocol
     * @return void
     */
    public static function register()
    {
        if (!stream_wrapper_register(self::PROTOCOL, self::class)) {
            throw new RuntimeException('Unable to register ' . self::class . ' protocol');
        }
    }

    /**
     * Return the filesize of the file
     * handle big files (filesize() doesn't)
     * @see http://us3.php.net/manual/fr/function.filesize.php#80959
     *
     * @param string $file Path to the file
     *
     * @return int the size of the file $file
     */
    public static function getSize($file)
    {
        $filename = escapeshellarg($file);
        return (int) trim(`stat -c%s $filename`);
    }

    /**
     * Workaround for the 2GB limitation
     * We can not use the php function md5_file
     *
     * @param string $file Path to the file
     *
     * @return string the md5sum of the file $file
     */
    public static function getMd5Sum($file)
    {
        //if filename containing spaces
        $filename = escapeshellarg($file);
        return trim(`md5sum $filename| awk '{ print $1 }'`);
    }

    /**
     * Tell if $file is a file
     * Handle big files (is_file() doesn't)
     * @see http://us3.php.net/manual/fr/function.is-file.php#81316
     *
     * @todo make it works on WIN32
     *
     * @param string $file Path to the file
     *
     * @return bool true if $file is a file
     */
    public static function isFile($file)
    {
        $filename = escapeshellarg($file);
        exec("[ -f $filename ]", $tmp, $ret);
        return $ret == 0;
    }

    /**
     * Open a (big) file
     *
     * @param string $path         Specifies the URL that was passed to the original function
     * @param string $mode         The mode to open the file, as detailed for fopen
     * @param int    $options      Holds additional flags set by the streams API.
     *                             It can hold one or more of the following values OR'd together.
     * @param string &$opened_path If the path is opened successfully, and STREAM_USE_PATH is set
     *                             in options, opened_path should be set to the full path of the
     *                             file/resource that was actually opened.
     *
     * @return bool true on success or false on failure
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->path   = preg_replace('`^' . preg_quote(self::PROTOCOL . '://') . '`', '', $path);
        $this->offset = 0;
        $this->mode   = $mode;
        $fileExists   = self::isFile($this->path) && is_readable($this->path);

        // Modes
        $fileMustExist  = false;
        $mustCreateFile = false;
        switch ($mode) {
            case 'r':
            case 'r+':
            case 'rb':
            case 'r+b':
            case 'rb+':
                if ($fileExists) {
                    $this->filesize = self::getSize($this->path);
                    return true;
                }
                return false;
            break;

            case 'w':
            case 'wb':
            case 'w+':
            case 'wb+':
            case 'w+b':
                if ($fileExists) {
                    $cmd = '>' . escapeshellarg($this->path);
                    `$cmd`;
                    return true;
                } else {
                    return touch($this->path);
                }
                break;

            case 'a':
            case 'ab':
            case 'a+':
            case 'ab+':
            case 'a+b':
                if ($fileExists) {
                    $this->offset = self::getSize($this->path);
                } else {
                    return touch($this->path);
                }
                break;
        }

        return true;
    }

    /**
     * Read for stream
     *
     * This method is called in response to fread()  and fgets().
     *
     * @param int $count How many bytes of data from the current position should be returned.
     *
     * @return string If there are less than count bytes available, return as many as are available. If no more data is available, return either FALSE or an empty string.
     */
    public function stream_read($count)
    {
        if ($this->filesize < PHP_INT_MAX) {
            // PHP 5.1 doesn't seems to like file_get_contents (bus error after 24MB)...
            // Note: it works with PHP 5.2.9 at least.
            // $read = file_get_contents($this->path, false, NULL, $this->offset, $count);
            $fd = fopen($this->path, 'rb');
            fseek($fd, $this->offset);
            $read = fread($fd, $count);
        } else {
            $read = $this->bigRead($count);
        }
        $this->offset += strlen($read);
        return $read;
    }

    /**
     * Manage read in files bigger than 2GB
     *
     * @param int $count How many bytes of data from the current position should be returned.
     *
     * @return string If there are less than count bytes available, return as many as are available. If no more data is available, return either FALSE or an empty string.
     */
    public function bigRead($count)
    {
        // ruby
        //$cmd = "ruby -e \"print File.read(". escapeshellarg($this->path) .", $count, $this->offset) || ''\"";

        // PERL
        $cmd = 'perl -e "open FH, ' . escapeshellarg($this->path) . '; seek(FH, ' . $this->offset . ', SEEK_SET); read FH, \\$d, ' . $count . '; print \\$d;"';

        // System: tail & head
        //tail --bytes=+$this->offset "$this->path" | head --bytes=$count`;

        //echo $cmd . PHP_EOL;
        $s = `$cmd`;
        return $s;
    }

    /**
     * Write for stream
     *
     * This method is called in response to fwrite().
     *
     * @param string $data
     *
     * @return Return the number of bytes that were successfully stored, or 0 if none could be stored.
     */
    public function stream_write($data)
    {
        $sizeToWrite = strlen($data);
        if ($this->offset + $sizeToWrite <= PHP_INT_MAX) {
            $written = file_put_contents($this->path, $data, FILE_APPEND);
        } else {
            $written = $this->bigWrite($data);
        }
        $this->offset += $written;
        return $written;
    }

    /**
     * Specific method to address files when they are bigger than 2GB
     *
     * @param string $data Should be stored into the underlying stream
     *
     * @return Return the number of bytes that were successfully stored, or 0 if none could be stored.
     */
    public function bigwrite($data)
    {
        $cmd = 'perl -e "use MIME::Base64; open FH, ' . escapeshellarg('>>' . $this->path) . '; print syswrite(FH, decode_base64(\'' . base64_encode($data) . '\')); close FH;"';
        //echo $cmd.PHP_EOL;
        $c   = `$cmd`;
        return $c;
    }

    /**
     * Tests for end-of-file on a file pointer
     *
     * This method is called in response to feof().
     *
     * @return Should return TRUE if the read/write position is at the end of the stream and if no more data is available to be read, or FALSE otherwise.
     */
    public function stream_eof()
    {
        //echo "$this->offset > $this->filesize\n";
        return $this->offset >= $this->filesize;
    }

    /**
     * Retrieve the current position of a stream
     *
     * This method is called in response to ftell().
     *
     * @return int Should return the current position of the stream.
     */
    public function stream_tell()
    {
        return $this->offset;
    }

    /**
     * Seeks to specific location in a stream
     *
     * This method is called in response to fseek().
     * The read/write position of the stream should be updated according to the offset and whence .
     *
     * @param int $offset The stream offset to seek to.
     * @param int $whence Possible values
     *                     * SEEK_SET - Set position equal to offset  bytes.
     *                     * SEEK_CUR - Set position to current location plus offset .
     *                     * SEEK_END - Set position to end-of-file plus offset .
     *
     * @return bool Return TRUE if the position was updated, FALSE otherwise
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < $this->filesize && $offset >= 0) {
                     $this->offset = $offset;
                     return true;
                } else {
                     return false;
                }
                break;

            case SEEK_CUR:
                if ($offset >= 0) {
                     $this->offset += $offset;
                     return true;
                } else {
                     return false;
                }
                break;

            case SEEK_END:
                if ($this->filesize + $offset >= 0) {
                     $this->offset = $this->filesize + $offset;
                     return true;
                } else {
                     return false;
                }
                break;

            default:
                return false;
        }
    }

    /**
     * Retrieve information about a file resource
     *
     * This method is called in response to fstat().
     *
     * @return array @see http://php.net/stat
     */
    public function stream_stat()
    {
        return stat($this->path);
    }
}

PHP_BigFile::register();
