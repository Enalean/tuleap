<?php
/**
 * GitPHP Pack
 *
 * Extracts data from a pack
 * Based on code from Glip by Patrik Fimml
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * Pack class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Pack
{

	const OBJ_COMMIT = 1;
	const OBJ_TREE = 2;
	const OBJ_BLOB = 3;
	const OBJ_TAG = 4;
	const OBJ_OFS_DELTA = 6;
	const OBJ_REF_DELTA = 7;

	/**
	 * project
	 *
	 * Stores the project internally
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * hash
	 *
	 * Stores the hash of the pack
	 *
	 * @access protected
	 */
	protected $hash;

	/**
	 * offsetCache
	 *
	 * Caches object offsets
	 *
	 * @access protected
	 */
	protected $offsetCache = array();

	/**
	 * indexModified
	 *
	 * Stores the index file last modified time
	 *
	 * @access protected
	 */
	protected $indexModified = 0;

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $hash pack hash
	 * @return mixed pack object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		if (!(preg_match('/[0-9A-Fa-f]{40}/', $hash))) {
			throw new Exception(sprintf(__('Invalid hash %1$s'), $hash));
		}
		$this->hash = $hash;
		$this->project = $project;

		if (!file_exists($project->GetPath() . '/objects/pack/pack-' . $hash . '.idx')) {
			throw new Exception('Pack index does not exist');
		}
		if (!file_exists($project->GetPath() . '/objects/pack/pack-' . $hash . '.pack')) {
			throw new Exception('Pack file does not exist');
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
	public function GetHash()
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
	 * @return boolean true if object is in pack
	 */
	public function ContainsObject($hash)
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
	private function FindPackedObject($hash)
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
			$version = GitPHP_Pack::fuint32($index);
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
	private function SearchIndexV1($index, $hash)
	{
		/*
		 * index v1 struture:
		 * fanout table - 256*4 bytes
		 * offset/sha table - 24*count bytes (4 byte offset + 20 byte sha for each index)
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
		 * binary serach for the index of the hash in the sha/offset listing
		 * between cur and after from the fanout
		 */
		while ($low <= $high) {
			$mid = ($low + $high) >> 1;
			fseek($index, 4*256 + 24*$mid);

			$off = GitPHP_Pack::fuint32($index);
			$binName = fread($index, 20);
			$name = bin2hex($binName);

			$this->offsetCache[$name] = $off;

			$cmp = strcmp($hash, $name);
			
			if ($cmp < 0) {
				$high = $mid - 1;
			} else if ($cmp > 0) {
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
	private function SearchIndexV2($index, $hash)
	{
		/*
		 * index v2 structure:
		 * magic and version - 2*4 bytes
		 * fanout table - 256*4 bytes
		 * sha listing - 20*count bytes
		 * crc checksums - 4*count bytes
		 * offsets - 4*count bytes
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
		fseek($index, 8 + 4*255);
		$objectCount = GitPHP_Pack::fuint32($index);

		/*
		 * binary search for the index of the hash in the sha listing
		 * between cur and after from the fanout
		 */
		$objIndex = false;
		while ($low <= $high) {
			$mid = ($low + $high) >> 1;
			fseek($index, 8 + 4*256 + 20*$mid);

			$binName = fread($index, 20);
			$name = bin2hex($binName);

			$cmp = strcmp($hash, $name);

			if ($cmp < 0) {
				$high = $mid - 1;
			} else if ($cmp > 0) {
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
		fseek($index, 8 + 4*256 + 24*$objectCount + 4*$objIndex);
		$offset = GitPHP_Pack::fuint32($index);
		if ($offset & 0x80000000) {
			throw new Exception('64-bit offsets not implemented');
		}
		return $offset;
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
	private function ReadFanout($index, $binaryHash, $offset)
	{
		/*
		 * fanout table has 255 4-byte integers
		 * indexed by the first byte of the object name.
		 * the value at that index is the index at which objects
		 * starting with that byte can be found
		 * (first level fan-out)
		 */
		if ($binaryHash{0} == "\x00") {
			$low = 0;
			fseek($index, $offset);
			$high = GitPHP_Pack::fuint32($index);
		} else {
			fseek($index, $offset + (ord($binaryHash{0}) - 1) * 4);
			$low = GitPHP_Pack::fuint32($index);
			$high = GitPHP_Pack::fuint32($index);
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
	public function GetObject($hash, &$type = 0)
	{
		$offset = $this->FindPackedObject($hash);
		if ($offset === false) {
			return false;
		}

		$pack = fopen($this->project->GetPath() . '/objects/pack/pack-' . $this->hash . '.pack', 'rb');
		flock($pack, LOCK_SH);

		$magic = fread($pack, 4);
		$version = GitPHP_Pack::fuint32($pack);
		if ($magic != 'PACK' || $version != 2) {
			flock($pack, LOCK_UN);
			fclose($pack);
			throw new Exception('Unsupported pack format');
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
	 */
	private function UnpackObject($pack, $offset)
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

		if ($type == GitPHP_Pack::OBJ_COMMIT || $type == GitPHP_Pack::OBJ_TREE || $type == GitPHP_Pack::OBJ_BLOB || $type == GitPHP_Pack::OBJ_TAG) {
			/*
			 * regular gzipped object data
			 */
			return array($type, gzuncompress(fread($pack, $size+512), $size));
		} else if ($type == GitPHP_Pack::OBJ_OFS_DELTA) {
			/*
			 * delta of an object at offset
			 */
			$buf = fread($pack, $size+512+20);

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
				$c = ord($buf{$pos++});
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
				$data = GitPHP_Pack::ApplyDelta($delta, $base);
				return array($type, $data);
			}
		} else if ($type == GitPHP_Pack::OBJ_REF_DELTA) {
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

			$data = GitPHP_Pack::ApplyDelta($delta, $base);

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
	private static function ApplyDelta($delta, $base)
	{
		/*
		 * algorithm from patch-delta.c
		 */
		$pos = 0;
		$baseSize = GitPHP_Pack::ParseVarInt($delta, $pos);
		$resultSize = GitPHP_Pack::ParseVarInt($delta, $pos);

		$data = '';
		$deltalen = strlen($delta);
		while ($pos < $deltalen) {
			$opcode = ord($delta{$pos++});
			if ($opcode & 0x80) {
				$off = 0;
				if ($opcode & 0x01) $off = ord($delta{$pos++});
				if ($opcode & 0x02) $off |= ord($delta{$pos++}) <<  8;
				if ($opcode & 0x04) $off |= ord($delta{$pos++}) << 16;
				if ($opcode & 0x08) $off |= ord($delta{$pos++}) << 24;
				$len = 0;
				if ($opcode & 0x10) $len = ord($delta{$pos++});
				if ($opcode & 0x20) $len |= ord($delta{$pos++}) <<  8;
				if ($opcode & 0x40) $len |= ord($delta{$pos++}) << 16;
				if ($len == 0) $len = 0x10000;
				$data .= substr($base, $off, $len);
			} else if ($opcode > 0) {
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
	private static function ParseVarInt($str, &$pos=0)
	{
		$ret = 0;
		$byte = 0x80;
		for ($shift = 0; $byte & 0x80; $shift += 7) {
			$byte = ord($str{$pos++});
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
		return GitPHP_Pack::uint32(fread($handle, 4));
	}
}
