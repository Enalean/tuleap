<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) 2011 Christopher Han <xiphux@gmail.com>
 *
 * Based on code from Glip by Patrik Fimml
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

namespace Tuleap\Git\GitPHP;

/**
 * Pack class
 *
 */
class Pack
{

    public const OBJ_COMMIT = 1;
    public const OBJ_TREE = 2;
    public const OBJ_BLOB = 3;
    public const OBJ_TAG = 4;
    public const OBJ_OFS_DELTA = 6;
    public const OBJ_REF_DELTA = 7;

    /**
     * @var Project
     */
    private $project;

    /**
     * hash
     *
     * Stores the hash of the pack
     */
    private $hash;

    /**
     * offsetCache
     *
     * Caches object offsets
     *
     */
    private $offsetCache = array();

    /**
     * indexModified
     *
     * Stores the index file last modified time
     *
     */
    private $indexModified = 0;

    /**
     * __construct
     *
     * Instantiates object
     *
     * @access public
     * @param mixed $project the project
     * @param string $hash pack hash
     * @throws \Exception exception on invalid hash
     */
    public function __construct($project, $hash)
    {
        if (!(preg_match('/[0-9A-Fa-f]{40}/', $hash))) {
            throw new \Exception(sprintf(dgettext("gitphp", 'Invalid hash %1$s'), $hash));
        }
        $this->hash = $hash;
        $this->project = $project;

        if (!file_exists($project->GetPath() . '/objects/pack/pack-' . $hash . '.idx')) {
            throw new \Exception('Pack index does not exist');
        }
        if (!file_exists($project->GetPath() . '/objects/pack/pack-' . $hash . '.pack')) {
            throw new \Exception('Pack file does not exist');
        }
    }

    /**
     * GetHash
     *
     * Gets the hash
     *
     * @access public
     * @return string object hash
     */
    public function GetHash() // @codingStandardsIgnoreLine
    {
        return $this->hash;
    }

    /**
     * ContainsObject
     *
     * Checks if an object exists in the pack
     *
     * @access public
     * @param string $hash object hash
     * @return bool true if object is in pack
     */
    public function ContainsObject($hash) // @codingStandardsIgnoreLine
    {
        if (!preg_match('/[0-9a-fA-F]{40}/', $hash)) {
            return false;
        }

        return $this->FindPackedObject($hash) !== false;
    }

    /**
     * FindPackedObject
     *
     * Searches for an object's offset in the index
     *
     * @return int offset
     * @param string $hash hash
     * @access private
     */
    private function FindPackedObject($hash) // @codingStandardsIgnoreLine
    {
        if (!preg_match('/[0-9a-fA-F]{40}/', $hash)) {
            return false;
        }

        $indexFile = $this->project->GetPath() . '/objects/pack/pack-' . $this->hash . '.idx';
        $mTime = filemtime($indexFile);
        if ($mTime > $this->indexModified) {
            $this->offsetCache = array();
            $this->indexModified = $mTime;
        }

        if (isset($this->offsetCache[$hash])) {
            return $this->offsetCache[$hash];
        }

        $offset = false;

        $index = fopen($indexFile, 'rb');
        flock($index, LOCK_SH);

        $magic = fread($index, 4);
        if ($magic == "\xFFtOc") {
            $version = Pack::fuint32($index);
            if ($version == 2) {
                $offset = $this->SearchIndexV2($index, $hash);
            }
        } else {
            $offset = $this->SearchIndexV1($index, $hash);
        }
        flock($index, LOCK_UN);
        fclose($index);
        $this->offsetCache[$hash] = $offset;
        return $offset;
    }

    /**
     * SearchIndexV1
     *
     * Seraches a version 1 index for a hash
     *
     * @access private
     * @param resource $index file pointer to index
     * @param string $hash hash to find
     * @return int pack offset if found
     */
    private function SearchIndexV1($index, $hash) // @codingStandardsIgnoreLine
    {
        /*
         * index v1 structure:
         * fanout table - 256*4 bytes
         * offset/sha table - 24*count bytes (4 byte offset + 20 byte sha for each index)
         *
         * @see https://git-scm.com/docs/pack-format#_original_version_1_pack_idx_files_have_the_following_format
         */

        $binaryHash = pack('H40', $hash);

        /*
         * get the start/end indices to search
         * from the fanout table
         */
        list($low, $high) = $this->ReadFanout($index, $binaryHash, 0);

        if ($low == $high) {
            return false;
        }

        /*
         * binary search for the index of the hash in the sha/offset listing
         * between cur and after from the fanout
         */
        while ($low <= $high) {
            $mid = ($low + $high) >> 1;
            fseek($index, 4 * 256 + 24 * $mid);

            $off = Pack::fuint32($index);
            $binName = fread($index, 20);
            $name = bin2hex($binName);

            $this->offsetCache[$name] = $off;

            $cmp = strcmp($hash, $name);

            if ($cmp < 0) {
                $high = $mid - 1;
            } elseif ($cmp > 0) {
                $low = $mid + 1;
            } else {
                return $off;
            }
        }

        return false;
    }

    /**
     * SearchIndexV2
     *
     * Seraches a version 2 index for a hash
     *
     * @access private
     * @param resource $index file pointer to index
     * @param string $hash hash to find
     * @return int pack offset if found
     */
    private function SearchIndexV2($index, $hash) // @codingStandardsIgnoreLine
    {
        /*
         * index v2 structure:
         * magic and version - 2*4 bytes
         * fanout table - 256*4 bytes
         * sha listing - 20*count bytes
         * crc checksums - 4*count bytes
         * offsets values - 4*count bytes
         * 8-bit offset entries - 8*count bytes
         *
         * @see https://git-scm.com/docs/pack-format#_version_2_pack_idx_files_support_packs_larger_than_4_gib_and
         */
        $binaryHash = pack('H40', $hash);

        /*
         * get the start/end indices to search
         * from the fanout table
         */
        list($low, $high) = $this->ReadFanout($index, $binaryHash, 8);
        if ($low == $high) {
            return false;
        }

        /*
         * get the object count from fanout[255]
         */
        fseek($index, 8 + 4 * 255);
        $objectCount = Pack::fuint32($index);

        /*
         * binary search for the index of the hash in the sha listing
         * between cur and after from the fanout
         */
        $objIndex = false;
        while ($low <= $high) {
            $mid = ($low + $high) >> 1;
            fseek($index, 8 + 4 * 256 + 20 * $mid);

            $binName = fread($index, 20);
            $name = bin2hex($binName);

            $cmp = strcmp($hash, $name);

            if ($cmp < 0) {
                $high = $mid - 1;
            } elseif ($cmp > 0) {
                $low = $mid + 1;
            } else {
                $objIndex = $mid;
                break;
            }
        }
        if ($objIndex === false) {
            return false;
        }

        /*
         * get the offset from the same index in the offset table
         */
        fseek($index, 8 + 4 * 256 + 24 * $objectCount + 4 * $objIndex);
        $offset = self::fuint32($index);
        if (($offset & 0x80000000) === 0) {
            return $offset;
        }

        $offset_in_64bit_entries_index = ($offset ^ 0x80000000);
        fseek($index, 8 + 4 * 256 + 24 * $objectCount + 4 * $objectCount + 8 * $offset_in_64bit_entries_index);
        return self::fuint64($index);
    }

    /**
     * ReadFanout
     *
     * Finds the start/end index a hash will be located between,
     * acconding to the fanout table
     *
     * @access private
     * @param resource $index index file pointer
     * @param string $binaryHash binary encoded hash to find
     * @param int $offset offset in the index file where the fanout table is located
     * @return array Range where object can be located
     */
    private function ReadFanout($index, $binaryHash, $offset) // @codingStandardsIgnoreLine
    {
        /*
         * fanout table has 255 4-byte integers
         * indexed by the first byte of the object name.
         * the value at that index is the index at which objects
         * starting with that byte can be found
         * (first level fan-out)
         */
        if ($binaryHash[0] == "\x00") {
            $low = 0;
            fseek($index, $offset);
            $high = Pack::fuint32($index);
        } else {
            fseek($index, $offset + (ord($binaryHash[0]) - 1) * 4);
            $low = Pack::fuint32($index);
            $high = Pack::fuint32($index);
        }
        return array($low, $high);
    }

    /**
     * GetObject
     *
     * Extracts an object from the pack
     *
     * @access public
     * @param string $hash hash of object to extract
     * @param int $type output parameter, returns the type of the object
     * @return string object content, or false if not found
     */
    public function GetObject($hash, &$type = 0) // @codingStandardsIgnoreLine
    {
        $offset = $this->FindPackedObject($hash);
        if ($offset === false) {
            return false;
        }

        $pack = fopen($this->project->GetPath() . '/objects/pack/pack-' . $this->hash . '.pack', 'rb');
        flock($pack, LOCK_SH);

        $magic = fread($pack, 4);
        $version = Pack::fuint32($pack);
        if ($magic != 'PACK' || $version != 2) {
            flock($pack, LOCK_UN);
            fclose($pack);
            throw new \Exception('Unsupported pack format');
        }

        list($type, $data) = $this->UnpackObject($pack, $offset);

        flock($pack, LOCK_UN);
        fclose($pack);
        return $data;
    }

    /**
     * UnpackObject
     *
     * Extracts an object at an offset
     *
     * @access private
     * @param resource $pack pack file pointer
     * @param int $offset object offset
     * @return array object type and data
     *
     * @see https://git-scm.com/docs/pack-format#_pack_pack_files_have_the_following_format
     */
    private function UnpackObject($pack, $offset) // @codingStandardsIgnoreLine
    {
        fseek($pack, $offset);

        /*
         * object header:
         * first byte is the type (high 3 bits) and low byte of size (lower 4 bits)
         * subsequent bytes each have 7 next higher bits of the size (little endian)
         * most significant bit is either 1 or 0 to indicate whether the next byte
         * should be read as part of the size.  1 means continue reading the size,
         * 0 means the data is starting
         */
        $c = ord(fgetc($pack));
        $type = ($c >> 4) & 0x07;
        $size = $c & 0x0F;
        for ($i = 4; $c & 0x80; $i += 7) {
            $c = ord(fgetc($pack));
            $size |= (($c & 0x7f) << $i);
        }

        if ($type == Pack::OBJ_COMMIT || $type == Pack::OBJ_TREE || $type == Pack::OBJ_BLOB || $type == Pack::OBJ_TAG) {
            /*
             * regular gzipped object data
             */
            return array($type, gzuncompress(fread($pack, $size + 512), $size));
        } elseif ($type == Pack::OBJ_OFS_DELTA) {
            /*
             * delta of an object at offset
             */
            $buf = fread($pack, $size + 512 + 20);

            /*
             * read the base object offset
             * each subsequent byte's 7 least significant bits
             * are part of the offset in decreasing significance per byte
             * (opposite of other places)
             * most significant bit is a flag indicating whether to read the
             * next byte as part of the offset
             */
            $pos = 0;
            $off = -1;
            do {
                $off++;
                $c = ord($buf[$pos++]);
                $off = ($off << 7) + ($c & 0x7f);
            } while ($c & 0x80);

            /*
             * next read the compressed delta data
             */
            $delta = gzuncompress(substr($buf, $pos), $size);
            unset($buf);

            $baseOffset = $offset - $off;
            if ($baseOffset > 0) {
                /*
                 * read base object at offset and apply delta to it
                 */
                list($type, $base) = $this->UnpackObject($pack, $baseOffset);
                $data = Pack::ApplyDelta($delta, $base);
                return array($type, $data);
            }
        } elseif ($type == Pack::OBJ_REF_DELTA) {
            /*
             * delta of object with hash
             */

            /*
             * first the base object's hash
             * load that object
             */
            $hash = fread($pack, 20);
            $hash = bin2hex($hash);
            $base = $this->project->GetObject($hash, $type);

            /*
             * then the gzipped delta data
             */
            $delta = gzuncompress(fread($pack, $size + 512), $size);

            $data = Pack::ApplyDelta($delta, $base);

            return array($type, $data);
        }

        return false;
    }

    /**
     * ApplyDelta
     *
     * Applies a binary delta to a base object
     *
     * @static
     * @access private
     * @param string $delta delta string
     * @param string $base base object data
     * @return string patched content
     */
    private static function ApplyDelta($delta, $base) // @codingStandardsIgnoreLine
    {
        /*
         * algorithm from patch-delta.c
         */
        $pos = 0;
        $baseSize = Pack::ParseVarInt($delta, $pos);
        $resultSize = Pack::ParseVarInt($delta, $pos);

        $data = '';
        $deltalen = strlen($delta);
        while ($pos < $deltalen) {
            $opcode = ord($delta[$pos++]);
            if ($opcode & 0x80) {
                $off = 0;
                if ($opcode & 0x01) {
                    $off = ord($delta[$pos++]);
                }
                if ($opcode & 0x02) {
                    $off |= ord($delta[$pos++]) <<  8;
                }
                if ($opcode & 0x04) {
                    $off |= ord($delta[$pos++]) << 16;
                }
                if ($opcode & 0x08) {
                    $off |= ord($delta[$pos++]) << 24;
                }
                $len = 0;
                if ($opcode & 0x10) {
                    $len = ord($delta[$pos++]);
                }
                if ($opcode & 0x20) {
                    $len |= ord($delta[$pos++]) <<  8;
                }
                if ($opcode & 0x40) {
                    $len |= ord($delta[$pos++]) << 16;
                }
                if ($len == 0) {
                    $len = 0x10000;
                }
                $data .= substr($base, $off, $len);
            } elseif ($opcode > 0) {
                $data .= substr($delta, $pos, $opcode);
                $pos += $opcode;
            }
        }
        return $data;
    }

    /**
     * ParseVarInt
     *
     * Reads a git-style packed variable length integer
     * sequence of bytes, where each byte's 7 less significant bits
     * are pieces of the int in increasing significance for each byte (little endian)
     * the most significant bit of each byte is a flag whether to continue
     * reading bytes or not
     *
     * @access private
     * @static
     * @param string $str packed data string
     * @param int $pos position in string to read from
     * @return int parsed integer
     */
    private static function ParseVarInt($str, &$pos = 0) // @codingStandardsIgnoreLine
    {
        $ret = 0;
        $byte = 0x80;
        for ($shift = 0; $byte & 0x80; $shift += 7) {
            $byte = ord($str[$pos++]);
            $ret |= (($byte & 0x7F) << $shift);
        }
        return $ret;
    }

    /**
     * uint32
     *
     * Unpacks a packed 32 bit integer
     *
     * @static
     * @access private
     * @return int integer
     * @param string $str binary data
     */
    private static function uint32($str)
    {
        $a = unpack('Nx', substr($str, 0, 4));
        return $a['x'];
    }

    /**
     * fuint32
     *
     * Reads and unpacks the next 32 bit integer
     *
     * @static
     * @access private
     * @return int integer
     * @param resource $handle file handle
     */
    private static function fuint32($handle)
    {
        return Pack::uint32(fread($handle, 4));
    }

    private static function uint64($str)
    {
        $a = unpack('Jx', substr($str, 0, 8));
        return $a['x'];
    }

    private static function fuint64($handle)
    {
        return self::uint64(fread($handle, 8));
    }
}
