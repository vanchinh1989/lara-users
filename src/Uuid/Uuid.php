<?php

namespace vanchinh1989\larausers\Uuid;

use Exception;

/**
 * Class Uuid.
 *
 * @property string $bytes
 * @property string $hex
 * @property string $node
 * @property string $string
 * @property string $time
 * @property string $urn
 * @property string $variant
 * @property string $version
 */
class Uuid
{
    const MD5 = 3;
    const SHA1 = 5;
    /**
     * 00001111  Clears all bits of version byte with AND.
     *
     * @var int
     */
    const CLEAR_VER = 15;

    /**
     * 00111111  Clears all relevant bits of variant byte with AND.
     *
     * @var int
     */
    const CLEAR_VAR = 63;

    /**
     * 11100000  Variant reserved for future use.
     *
     * @var int
     */
    const VAR_RES = 224;

    /**
     * 11000000  Microsoft UUID variant.
     *
     * @var int
     */
    const VAR_MS = 192;

    /**
     * 10000000  The RFC 4122 variant (this variant).
     *
     * @var int
     */
    const VAR_RFC = 128;

    /**
     * 00000000  The NCS compatibility variant.
     *
     * @var int
     */
    const VAR_NCS = 0;

    /**
     * 00010000.
     *
     * @var int
     */
    const VERSION_1 = 16;

    /**
     * 00110000.
     *
     * @var int
     */
    const VERSION_3 = 48;

    /**
     * 01000000.
     *
     * @var int
     */
    const VERSION_4 = 64;

    /**
     * 01010000.
     *
     * @var int
     */
    const VERSION_5 = 80;

    /**
     * Time (in 100ns steps) between the start of the UTC and Unix epochs.
     *
     * @var int
     */
    const INTERVAL = 0x01B21DD213814000;

    /**
     * @var string
     */
    const NS_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    /**
     * @var string
     */
    const NS_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';

    /**
     * @var string
     */
    const NS_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';

    /**
     * @var string
     */
    const NS_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Regular expression for validation of UUID.
     */
    const VALID_UUID_REGEX = '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$';

    /**
     * @param string $uuid
     *
     * @throws Exception
     */
    protected function __construct($uuid)
    {
        if (!empty($uuid) && strlen($uuid) !== 16) {
            throw new Exception('Input must be a 128-bit integer.');
        }

        $this->bytes = $uuid;

        // Optimize the most common use
        $this->string = bin2hex(substr($uuid, 0, 4)).'-'.
            bin2hex(substr($uuid, 4, 2)).'-'.
            bin2hex(substr($uuid, 6, 2)).'-'.
            bin2hex(substr($uuid, 8, 2)).'-'.
            bin2hex(substr($uuid, 10, 6));
    }

    /**
     * @param int    $ver
     * @param string $node
     * @param string $ns
     *
     * @throws Exception
     *
     * @return Uuid
     */
    public static function generate($ver = 1, $node = null, $ns = null)
    {
        /* Create a new UUID based on provided data. */
        switch ((int) $ver) {
            case 1:
                return new static(static::mintTime($node));
            case 2:
                // Version 2 is not supported
                throw new Exception('Version 2 is unsupported.');
            case 3:
                return new static(static::mintName(static::MD5, $node, $ns));
            case 4:
                return new static(static::mintRand());
            case 5:
                return new static(static::mintName(static::SHA1, $node, $ns));
            default:
                throw new Exception('Selected version is invalid or unsupported.');
        }
    }

    /**
     * Generates a Version 1 UUID.
     * These are derived from the time at which they were generated.
     *
     * @param string $node
     *
     * @return string
     */
    protected static function mintTime($node = null)
    {

        /** Get time since Gregorian calendar reform in 100ns intervals
         * This is exceedingly difficult because of PHP's (and pack()'s)
         * integer size limits.
         * Note that this will never be more accurate than to the microsecond.
         */
        $time = microtime(1) * 10000000 + static::INTERVAL;

        // Convert to a string representation
        $time = sprintf('%F', $time);

        //strip decimal point
        preg_match("/^\d+/", $time, $time);

        // And now to a 64-bit binary representation
        $time = base_convert($time[0], 10, 16);
        $time = pack('H*', str_pad($time, 16, '0', STR_PAD_LEFT));

        // Reorder bytes to their proper locations in the UUID
        $uuid = $time[4].$time[5].$time[6].$time[7].$time[2].$time[3].$time[0].$time[1];

        // Generate a random clock sequence
        $uuid .= static::randomBytes(2);

        // set variant
        $uuid[8] = chr(ord($uuid[8]) & static::CLEAR_VAR | static::VAR_RFC);

        // set version
        $uuid[6] = chr(ord($uuid[6]) & static::CLEAR_VER | static::VERSION_1);

        // Set the final 'node' parameter, a MAC address
        if (!is_null($node)) {
            $node = static::makeBin($node, 6);
        }

        // If no node was provided or if the node was invalid,
        //  generate a random MAC address and set the multicast bit
        if (is_null($node)) {
            $node = static::randomBytes(6);
            $node[0] = pack('C', ord($node[0]) | 1);
        }

        $uuid .= $node;

        return $uuid;
    }

    /**
     * Randomness is returned as a string of bytes.
     *
     * @param $bytes
     *
     * @return string
     */
    public static function randomBytes($bytes)
    {
        return random_bytes($bytes);
    }

    /**
     * Insure that an input string is either binary or hexadecimal.
     * Returns binary representation, or false on failure.
     *
     * @param string $str
     * @param int    $len
     *
     * @return string|null
     */
    protected static function makeBin($str, $len)
    {
        if ($str instanceof self) {
            return $str->bytes;
        }
        if (strlen($str) === $len) {
            return $str;
        } else {
            // strip URN scheme and namespace
            $str = preg_replace('/^urn:uuid:/is', '', $str);
        }
        // strip non-hex characters
        $str = preg_replace('/[^a-f0-9]/is', '', $str);
        if (strlen($str) !== ($len * 2)) {
            return null;
        } else {
            return pack('H*', $str);
        }
    }

    /**
     * Generates a Version 3 or Version 5 UUID.
     * These are derived from a hash of a name and its namespace, in binary form.
     *
     * @param string $ver
     * @param string $node
     * @param string $ns
     *
     * @throws Exception
     *
     * @return string
     */
    protected static function mintName($ver, $node, $ns)
    {
        if (empty($node)) {
            throw new Exception('A name-string is required for Version 3 or 5 UUIDs.');
        }

        // if the namespace UUID isn't binary, make it so
        $ns = static::makeBin($ns, 16);
        if (is_null($ns)) {
            throw new Exception('A binary namespace is required for Version 3 or 5 UUIDs.');
        }

        $version = null;
        $uuid = null;

        switch ($ver) {
            case static::MD5:
                $version = static::VERSION_3;
                $uuid = md5($ns.$node, 1);
                break;
            case static::SHA1:
                $version = static::VERSION_5;
                $uuid = substr(sha1($ns.$node, 1), 0, 16);
                break;
            default:
                // no default really required here
        }

        // set variant
        $uuid[8] = chr(ord($uuid[8]) & static::CLEAR_VAR | static::VAR_RFC);

        // set version
        $uuid[6] = chr(ord($uuid[6]) & static::CLEAR_VER | $version);

        return $uuid;
    }

    /**
     * Generate a Version 4 UUID.
     * These are derived solely from random numbers.
     * generate random fields.
     *
     * @return string
     */
    protected static function mintRand()
    {
        $uuid = static::randomBytes(16);
        // set variant
        $uuid[8] = chr(ord($uuid[8]) & static::CLEAR_VAR | static::VAR_RFC);
        // set version
        $uuid[6] = chr(ord($uuid[6]) & static::CLEAR_VER | static::VERSION_4);

        return $uuid;
    }

    /**
     * Import an existing UUID.
     *
     * @param string $uuid
     *
     * @return Uuid
     */
    public static function import($uuid)
    {
        return new static(static::makeBin($uuid, 16));
    }

    /**
     * Compares the binary representations of two UUIDs.
     * The comparison will return true if they are bit-exact,
     * or if neither is valid.
     *
     * @param string $a
     * @param string $b
     *
     * @return string|string
     */
    public static function compare($a, $b)
    {
        if (static::makeBin($a, 16) == static::makeBin($b, 16)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $var
     *
     * @return string|string|number|number|number|number|number|null|number|null|null
     */
    public function __get($var)
    {
        switch ($var) {
            case 'bytes':
                return $this->bytes;
                break;
            case 'hex':
                return bin2hex($this->bytes);
                break;
            case 'node':
                if (ord($this->bytes[6]) >> 4 == 1) {
                    return bin2hex(substr($this->bytes, 10));
                } else {
                    return null;
                }
                break;
            case 'string':
                return $this->__toString();
                break;
            case 'time':
                if (ord($this->bytes[6]) >> 4 == 1) {
                    // Restore contiguous big-endian byte order
                    $time = bin2hex($this->bytes[6].$this->bytes[7].$this->bytes[4].$this->bytes[5].
                        $this->bytes[0].$this->bytes[1].$this->bytes[2].$this->bytes[3]);
                    // Clear version flag
                    $time[0] = '0';

                    // Do some reverse arithmetic to get a Unix timestamp
                    return (hexdec($time) - static::INTERVAL) / 10000000;
                } else {
                    return null;
                }
                break;
            case 'urn':
                return 'urn:uuid:'.$this->__toString();
                break;
            case 'variant':
                $byte = ord($this->bytes[8]);
                if ($byte >= static::VAR_RES) {
                    return 3;
                } elseif ($byte >= static::VAR_MS) {
                    return 2;
                } elseif ($byte >= static::VAR_RFC) {
                    return 1;
                } else {
                    return 0;
                }
                break;
            case 'version':
                return ord($this->bytes[6]) >> 4;
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * Return the UUID.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->string;
    }

    /**
     * Import and validate an UUID.
     *
     * @param Uuid|string $uuid
     *
     * @return bool
     */
    public static function validate($uuid)
    {
        return (bool) preg_match('~'.static::VALID_UUID_REGEX.'~', static::import($uuid)->string);
    }
}