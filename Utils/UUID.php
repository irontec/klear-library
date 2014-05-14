<?php

namespace Iron\Utils;

/**
 * Adaptada desde:
 * http://framework.zend.com/wiki/display/ZFPROP/Zend_Utility_Uuid+-+Stephan+Wentz
 */
class UUID {

     /**
     * 32-bit integer that identifies this host
     *
     * @var integer
     */
    protected static $_node = null;

    /**
     * Process identifier
     *
     * @var integer
     */
    protected static $_pid  = null;

    /**
     * Returns a 32-bit integer that identifies this host.
     *
     * The node identifier needs to be unique among nodes
     * in a cluster for a given application in order to
     * avoid collisions between generated identifiers.
     *
     * @return integer
     */
    protected static function _getNodeId()
    {
        $ip       = null;
        $hostname = null;

        // if we are an a request, use IP from server array
        if (isset($_SERVER['SERVER_ADDR']))
        {
            $ip = $_SERVER['SERVER_ADDR'];
        }

        // try to determine IP through php functions
        if (!$ip === null)
        {
            // introduced in php 5.3
            if (function_exists('gethostname'))
            {
                $hostname = gethostname();
            }
            else
            {
                $hostname = php_uname('n');
            }

            // if we found an hostname, lookup IP for it
            if ($hostname)
            {
                $ip = gethostbyname($hostname);

                // if we don't get an ip result
                if (!$ip)
                {
                    $ip = crc32($hostname);
                }
            }
        }

        // use localhost IP as fallback
        if ($ip === null)
        {
            $ip = '127.0.0.1';
        }

        return ip2long($ip);
    }

    /**
     * Returns a process identifier.
     *
     * In multi-process servers, this should be the system process ID.
     * In multi-threaded servers, this should be some unique ID to
     * prevent two threads from generating precisely the same UUID
     * at the same time.
     *
     * @return integer
     */
    protected static function _getLockId()
    {
        if (function_exists('zend_thread_id'))
        {
            return zend_thread_id();
        }

        return getmypid();
    }

    /**
     * Generate an RFC 4122 UUID.
     *
     * This is psuedo-random UUID influenced by the system clock, IP
     * address and process ID.
     *
     * The intended use is to generate an identifier that can uniquely
     * identify user generated posts, comments etc. made to a website.
     * This generation process should be sufficient to avoid collisions
     * between nodes in a cluster, and between apache children on the
     * same host.
     *
     * @return string
     */
    public static function generate()
    {
        if (function_exists('uuid_create'))
        {
            return uuid_create();
        }

        if (self::$_node === null)
        {
            self::$_node = self::_getNodeId();
        }

        if (self::$_pid === null)
        {
            self::$_pid = self::_getLockId();
        }

        list($timeMid, $timeLow) = explode(' ', microtime());

        $timeLow = (int)$timeLow;
        $timeMid = (int)substr($timeMid, 2);

        $timeHighAndVersion = mt_rand(0, 0xfff);
        /* add version 4 UUID identifier */
        $timeHighAndVersion |= 0x4000;

        $clockSeqLow = mt_rand(0, 0xff);

        /* type is pseudo-random */
        $clockSeqHigh  = mt_rand(0, 0x3f);
        $clockSeqHigh |= 0x80;

        $nodeLow = self::$_pid;
        $node    = self::$_node;

        return sprintf(
            "%08x-%04x-%04x-%02x%02x-%04x%08x",
            $timeLow, $timeMid & 0xffff, $timeHighAndVersion,
            $clockSeqHigh, $clockSeqLow,
            $nodeLow, $node
        );
    }

}