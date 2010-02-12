<?php
/**
 * @copyright Nicolas Terray
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
 */

/**
 * Allow php to handle big files
 */
class PHP_BigFile {
    
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
    const PROTOCOL = 'php-bigfile';

    /**
     * @return string the stream url to use with fopen & co
     */
    public static function stream($file) {
        return self::PROTOCOL .'://'. $file;
    }

    /**
     * Register the wrapper
     * 
     * @throw RuntimeException if we cannot register the protocol
     * @return void
     */
    public static function register() {
        if (!stream_wrapper_register(self::PROTOCOL, __CLASS__)) {
            throw new RuntimeException('Unable to register '. __CLASS__ .' protocol');
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
    public static function getSize($file) {
        if (DIRECTORY_SEPARATOR === '/') {
            $filename = escapeshellarg($file);
            $size = trim(`stat -c%s $filename`);
        } else {
            $fsobj = new COM("Scripting.FileSystemObject");
            $f = $fsobj->GetFile($file);
            $size = $file->Size;
        }
        return $size;
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
    public static function isFile($file) {
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
     * @return boolean true on success or false on failure
     */
    public function stream_open($path, $mode, $options, &$opened_path) {
        $this->path = preg_replace('`^'. preg_quote(self::PROTOCOL .'://') .'`', '', $path);
        $this->offset = 0;
        if (self::isFile($this->path) && is_readable($this->path)) {
            $this->filesize = self::getSize($this->path);
            return true;
        }
        return false;
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
    public function stream_read($count) {
        // ruby
        //$cmd = "ruby -e \"print File.read(". escapeshellarg($this->path) .", $count, $this->offset) || ''\"";
        
        // PERL
        $cmd = 'perl -e "open FH, '. escapeshellarg($this->path) .'; seek(FH, '. $this->offset .', SEEK_SET); read FH, \\$d, '. $count .'; print \\$d;"';
        
        // System: tail & head
        //tail --bytes=+$this->offset "$this->path" | head --bytes=$count`;

        //echo $cmd . PHP_EOL;
        $s = `$cmd`;
        $this->offset += strlen($s);
        return $s;
    }
    
    /**
     * Tests for end-of-file on a file pointer
     *
     * This method is called in response to feof(). 
     *
     * @return Should return TRUE if the read/write position is at the end of the stream and if no more data is available to be read, or FALSE otherwise.
     */
    public function stream_eof() {
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
    public function stream_tell() {
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
     * @return boolean Return TRUE if the position was updated, FALSE otherwise
     */
    public function stream_seek($offset, $whence = SEEK_SET) {
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

}

PHP_BigFile::register();

?>
