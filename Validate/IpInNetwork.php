<?php
/**
* AWD Framework
*
* LICENSE
*
* This source file is subject to the Open Software License 3.0 (OSL-3.0)
* It is available at this URL: http://www.adrianworlddesign.com/licenses/OSL-v30
* and directly with OSI at URL: http://www.opensource.org/licenses/OSL-3.0.
* If you are not able to receive a copy of this license please send an email
* to license at adrianworlddesign.com.
*
* @category Awd
* @copyright Copyright (c) 2009-2012 Adrian World Design (http://www.adrianworlddesign.com)
* @license http://www.adrianworlddesign.com/licenses/OSL-v30 or http://www.opensource.org/licenses/OSL-3.0    OSL-3.0
* @version 0.1.36, 2012/01/16 14:37:00 CST
* @author Adrian World (aw)
*
*/
class Iron_Validate_IpInNetwork extends Zend_Validate_Ip {

    const NOT_IN_NETWORK  = 'notInNetwork';
    const LOW_IN_NETWORK  = 'lowInNetwork';
    const HIGH_IN_NETWORK = 'highInNetwork';
    const INVALID_NETWORK = 'invalidNetwork';
    const MISSING_NETWORK = 'missingNetwork';

    /**
     * A CIDR number (valid values 0-32)
     * @var int
     */
    protected $_cidr = 0;

    /**
     * A decimal 32-bit netmask
     * @var string
     */
    protected $_netmask = '255.255.255.255';

    /**
     * A 4-octet IPv4 network address
     * @var string
     */
    protected $_network = null;

    /**
     * A network/mask notation or network range
     * @var string
     */
    protected $_notation = null;

    /**
     * Unsigned decimal "from" IP address
     * @var string
     */
    protected $_rangeFrom = null;

    /**
     * Unsigned decimal "to" IP address
     * @var string
     */
    protected $_rangeTo = 0;

    /**
     * Will throw Exception instead of trigger_error if true
     * @var false
     */
    protected $_throw = false;

    /**
     * Constructor for IpInNetwork class
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/14 13:34:00 CST
     * @author aw
     * @desc <p>Accepts an array with options. Also adds the error messages to the parent's message templates.</p>
     * @example <p>List of allow options and their use:
     * $options argument must be an array and allows two key/value pairs for this class and passes on any remaining
     * values to the parent class Zend_Validate_Ip. If key 'network' exists it will pass on the value to method
     * setNetworkNotation and for key 'throw' to setThrow4Notation.</p>
     * @see Zend_Validate_Ip::__construct()
     * @see Awd_Validate_IpInNetwork::setNetworkNotation()
     * @see Awd_Validate_IpInNetwork::setThrow4Notation()
     * @param array $options
     * @return void
     */
    public function __construct($options = array()) {
        if ( !empty($options) && is_array($options) ) {
                    if ( array_key_exists('throw',$options) ) {
                $this->setThrow4Notation($options['throw']);
                unset($options['throw']);
            }
            if ( array_key_exists('network',$options) ) {
                $this->setNetworkNotation($options['network']);
                unset($options['network']);
            }
        }

        $this->setMessages(array());

        parent::__construct($options);
    }

    /**
     * (non-PHPdoc)
     * @see Zend_Validate_Abstract::setMessages()
     */
    public function setMessages(array $messages) {
        $newMessages = array(
            self::MISSING_NETWORK => 'No valid network has been given to validate against',
            self::INVALID_NETWORK => 'The network is not an accepted format',
            self::NOT_IN_NETWORK  => "The ip '%value%' does not match the provided 32 network",
            self::LOW_IN_NETWORK  => "The ip '%value%' is lower in range than the provided network",
            self::HIGH_IN_NETWORK => "The ip '%value%' is higher in range than the provided network",
        );

        foreach ( $newMessages as $messageKey => $messageString ) {
            if ( !isset($this->_messageTemplates[$messageKey]) ) {
                $this->_messageTemplates[$messageKey] = $messageString;
            } elseif ( !empty($messages) && array_key_exists($messageKey,$messages) ) {
                $this->_messageTemplates[$messageKey] = $messages[$messageKey];
                unset($messages[$messageKey]);
            }
        }

        empty($messages) || parent::setMessages($messages) ;

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Zend_Validate_Ip::isValid()
     */
    public function isValid($value) {
        if ( true === parent::isValid($value) ) {
            $notation = $this->_getNotation();
            if ( !empty($notation) ) {
                // a valid notation has been set
                $network = $this->_getNetwork();
                if ( !empty($network) ) {
                    if ( true === $this->_validateIpInNetwork($value) ) {
                        return true;
                    }
                } else {
                    if ( true === $this->_validateIpInRange($value) ) {
                        return true;
                    }
                }
                // NOTE: Errors are only available in regards to the value (ip address) and not the network/netmask (notation)
                $errors = $this->getErrors();
                if ( empty($errors) ) {
                    $this->_error(self::NOT_IN_NETWORK);
                }
            } else {
                $this->_error(self::MISSING_NETWORK);
            }
        }

        return false;
    }

    /**
     * Validates the IP in a given network
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/14 16:34:00 CST
     * @author aw
     * @desc <p>Takes the CIDR and network (IP) address and validates the given IP address against it. Sets the appropriate
     * errors if the IP is not a match for the network.</p>
     * @param string $ip
     * @return bool
     */
    protected function _validateIpInNetwork($ip) {
        $netmask = $this->getCidr();
        $network = $this->_getNetwork();

        // lets get this out of the way first
        if ( 32 === $netmask ) {
            // this network has to match the IP
            if ( $network === $ip ) {
                return true;
            } else {
                $this->_error(self::NOT_IN_NETWORK);
                return false;
            }
        }

        // get the unsigned integers for the IP and network address
        $ip_addr_uDec  = $this->_makeUnsignedAddress($ip);
        $lNetwork_uDec = $this->_makeUnsignedAddress($network);

        // let verify the IP against the lower end of the range
        if ( $ip_addr_uDec < $lNetwork_uDec ) {
            // the ip is below the network range
            $this->_error(self::LOW_IN_NETWORK);
            return false;
        }

        // well then, finally verify the IP against the uppoer end of the range

        // add the decimal representation of the netmask to the network IP
        $netmask_uDec1 = $netmask < 31 ? pow(2, (32-$netmask)) - 1 : 1 ;
        $netmask_uDec = pow(2, 32-$netmask) - 1 ;
        $uNetwork_uDec = $lNetwork_uDec + $netmask_uDec;

        if ( $ip_addr_uDec > $uNetwork_uDec ) {
            // the ip is above the network range
            $this->_error(self::HIGH_IN_NETWORK);
            return false;
        }

        return true;
    }

    /**
     * Validates the IP in a given range
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/16 13:06:00 CST
     * @author aw
     * @desc <p>Takes the "from" and "to" (IP) address and validates the given IP address against it. Sets the appropriate
     * errors if the IP is not within the defined range.</p>
     * @param string $ip
     * @return bool
     */
    protected function _validateIpInRange($ip) {
        $uInt_Ip = $this->_makeUnsignedAddress($ip);

        if ( is_numeric($this->_rangeFrom) && $uInt_Ip >= $this->_rangeFrom ) {
            if ( $uInt_Ip <= $this->_rangeTo ) {
                return true;
            } elseif ( is_numeric($this->_rangeTo) ) {
                $this->_error(self::HIGH_IN_NETWORK);
                return false;
            }
        } elseif ( is_numeric($this->_rangeFrom) ) {
            $this->_error(self::LOW_IN_NETWORK);
            return false;
        }

        $this->_error(self::MISSING_NETWORK);
        return false;
    }

    /**
     * Set the network (notation) to the properties
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/14 13:43:00 CST
     * @author aw
     * @desc <p>The network is usually a notation with a network/netmask combination. The method uses two methods to validate
     * the netmask and the network address. If the notation is a range fromIPAddress-toIPAddress the netmask and network address
     * are ignored. The isValid() will then attempt to validate the value within the range and not the network segment.</p>
     *  string $notation network/address (128.0.0.0/24) (128.0.0.0/255.255.255.0) or (128.0.0.0-128.0.0.255)
     * @return object|false Awd_Validate_IpInNetwork
     */
    public function setNetworkNotation($notation) {
        $network = false !== strpos($notation, '/') ? $this->_evaluateNetmask($notation) : false ;
        if ( false !== $network) {
            // a valid CIDR/netmask has been found
            if ( true === parent::isValid($network) ) {
                if ( $this->_validateNetwork($network) ) {
                    $this->_network  = $network;
                    $this->_notation = $notation;

                    return $this;
                }
            } else {
                $this->_invalidNetwork(__LINE__);
            }
        } elseif ( false !== strpos($notation, '-') ) {
            // the notation is looking like a from-to IP range
            if ( true === $this->_validateRange($notation) ) {
                $this->_notation = $notation;

                return $this;
            }
        }

        return false;
    }

    /**
     * Sets the value for _throw property
     *
     * @since Version 0.1.36
     * @version 0.1.35 2012/01/17 08:23:00 CST
     * @author aw
     * @desc <p>The value determines if the application will throw an exception or trigger an E_USER_WARNING if
     * an error was found in the submitted network notation. The default is false.</p>
     * @throws E_USER_WARNING if the argument is not of type bool
     *  bool $throw
     * @return object Awd_Validate_IpInNetwork
     */
    public function setThrow4Notation($throw = false) {
        if ( !is_bool($throw) ) {
            $msg = '[AWD] Programming error: The argument is not a boolean value';
            trigger_error($msg,E_USER_WARNING);
        }


        $this->_throw = $throw;
        return $this;
    }

    /**
     * Gets the value for _throw property
     *
     * @since Version 0.1.36
     * @version 0.1.35 2012/01/17 08:27:00 CST
     * @author aw
     * @desc <p>The value determines if the application will throw an exception or trigger an E_USER_WARNING if
     * an error was found in the submitted network notation. The default is false.</p>
     * @return bool
     */
    public function getThrow4Notation() {
        return (bool) $this->_throw;
    }

    /**
     * Gets the network (notation) as it has been set if valid
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/14 16:08:00 CST
     * @author aw
     * @desc <p>If empty the network (notation) was either not set or not valid. Hence, this method can be used to
     * verify if setting a network range or notation was successful with the constructor.</p>
     * @return string
     */
    public function getNetworkNotation() {
        return (string) $this->_getNotation();
    }

    /**
     * Protected method to gets the network (notation) as it has been set if valid
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/14 16:08:00 CST
     * @author aw
     * @desc <p>Note that the notation is only available when it passed the internal validation. Internally (protected)
     * the network represents the network (IP) address whereas the notation is the full string as set when is valid.
     * The notation is a representation of network range or network/mask. This method essentially returns internally
     * (protected) the same result as the public method getNetworkNotation().</p>
     * @return string|null
     */
    protected function _getNotation() {
        return empty($this->_notation) ? null : (string) $this->_notation ;
    }

    /**
     * Gets the network address from the notation if a valid address and mask has been set
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/14 16:18:00 CST
     * @author aw
     * @desc <p>Note that internally (protected) the network represents the network (IP) address extracted from the
     * "network notation", i.e. a representation of network range or network/mask. If the notation was not valid or a
     * network range has been set this value will be empty.</p>
     * @return string
     */
    protected function _getNetwork() {
        return (string) $this->_network;
    }

    /**
     * Gets the CIDR from the notation if a valid address and mask has been set
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/14 16:26:00 CST
     * @author aw
     * @desc <p>The CIDR has been extracted from the "network notation", i.e. a representation of network/mask or
     * network/CIDR. If the notation was not valid or a network range has been set this value will be empty.</p>
     * @return int
     */
    public function getCidr() {
        return (int) $this->_cidr;
    }

    /**
     * Evaluates the netmask from a notation
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/15 10:12:00 CST
     * @author aw
     * @desc <p>The notation is usually set as a {network/CIDR} or {network/netmask} notation. This method examines
     * the string following a slash. A CIDR mask will be verified for its number whereas a netmask is passed to
     * another method _validateNetmask() for validation and if valid converted into a CIDR representation. In
     * either case if the value is valid the remaining network (IP) address is returned or false on failure.</p>
     * @throws Calls method _invalidNetwork() when a failure is detected
     * @param string $notation
     * @return string|bool (false)
     */
    protected function _evaluateNetmask($notation) {
        // split the notation in network and netmask information
        list($network, $netmask) = explode('/', $notation, 2);
        if ( is_numeric($netmask) ) {
            // does look like a CIDR netmask
            $between = new Zend_Validate_Between(array('min'=>1,'max'=>32));
            if ( true === $between->isValid($netmask) ) {
                $this->_cidr = (int) $netmask;
                return $network;
            } else {
                $error_msgs = $between->getMessages();
                if ( !empty($error_msgs) && is_array($error_msgs) ) {
                    $msg = array_shift($error_msgs);
                } else {
                    // fallback, should not really be an option
                    $msg = sprintf('The netmask [ %s ] is not a valid option',$netmask);
                }

                // oops, this CIDR is not a valid range
                return $this->_invalidNetwork(__LINE__.' - '.$msg);
            }
        } elseif ( !empty($netmask) ) {
            // looks more like 32-bit (like 255.255.255.0) format
            if ( true === ($line = $this->_validateNetmask($netmask)) ) {
                return $network;
            }

            return $this->_invalidNetwork($line);
        }

        return $this->_invalidNetwork(__LINE__);
    }

    /**
     * Validates a 32-bit netmask
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/16 10:34:00 CST
     * @author aw
     * @desc <p>A netmask is a decimal representation of 32-bit string where the beginning sequence is a complete
     * set of 1 (one) followed by a complete set of 0 (zero). If valid the netmask string will be a CIDR numeric
     * value and set to the proected property _cidr. If not valid the returned value is the line plus the index if
     * the failure is in one of the segments.</p>
     * @param string $netmask
     * @return true|string
     */
    protected function _validateNetmask($netmask) {
        $classes = explode('.', $netmask);
        if ( 4 !== count($classes) ) {
            return __LINE__;
        }

        $cidr = 0; $end = false;
        foreach ( $classes as $index => $segment ) {
            if ( !is_numeric($segment) ) {
                return __LINE__;
            } elseif ( 0 === (int) $segment ) {
                $end = true;  // all following segment have to be 0 (zero) as well
                continue;
            }
            $matches = array();

            // evaluate the binary representation of the segment
            $bin = decbin($segment);
            if ( 8 !== strlen($bin) || 0 === preg_match('/^([1]{1,8})([0]*)$/', decbin($segment), $matches) ) {
                if ( 8 !== strlen($bin) ) {
                    // this segment is not a complete byte (8 bits) i.e. a value below 128
                    return __LINE__.':'.++$index;  // NOTE: Index begins at 0 (zero)
                }
                // this segment is a complete byte (8 bits), i.e.  a value above 128, but not a valid binary mask (like 11110000)
                return __LINE__.':'.++$index;  // NOTE: Index begins at 0 (zero)
            } elseif ( true === $end ) {
                // a mask was found in the previous segment; therefore, this segment should be 0 (zero)
                return __LINE__.':'.++$index;  // NOTE: Index begins at 0 (zero)
            }
            $len = strlen($matches[1]);
            if ( $len < 8 ) { $end = true; }
            $cidr += $len;
        }

        $this->_cidr = $cidr;
        return true;
    }

    /**
     * Validates the network address in a subnet notation
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/16 10:34:00 CST
     * @author aw
     * @desc <p>The network address in a CIDR or subnet mask notation is the base of the assigned block.
     * Because the size of the block is specified by the CIDR or subnet mask the base of a network address
     * has to fit and match into the block size. This method evaluates the block size and then validates
     * if the base of network address fits into the assigned block. If not valid the line plus the index
     * of the failed segment is sent to method _invalidNetwork() triggering or throwing an error.</p>
     * @param string $network
     * @return true|string
     */
    protected function _validateNetwork($network) {
        $cidr   = $this->getCidr();
        $class  = $cidr / 8;

        // an integer indicates a classful (unicast) network
        if ( is_int($class) ) {
            $iClass   = $class;
            $maskBits = 0;
        } else {
            $iClass   = (int) floor($class);
            $maskBits = (int) 8 - ($cidr - ($iClass * 8));
            $hosts    = (int) pow(2, $maskBits);  // number of usable hosts in a subnet
        }

        $segments = explode('.', $network);
        // Note: $segments index begins at 0 (zero) and $iClass is the last complete segment in the netmask (8 bits (255))
        // It is irrelevant but just to clarify for $iClass: 1 = Class A, 2 = Class B, 3 = Class C

        $complete = false;
        // check all segments following the last complete class and because we have to check for
        // subnetting in the _follow_ class we do NOT add 1 to $iClass as the index in $segments
        for ($index = $iClass; $index < 4; $index++) {
            $subNetwork = (int) $segments[$index];

            if ( 0 === $maskBits ) {
                // this class has no subnets (aka classful network)
                // all 0 (zero) are expected as (sub)network numbers
                if ( 0 !== $subNetwork ) {
                    return $this->_invalidNetwork(__LINE__.':'.++$index);  // NOTE: Index begins at 0 (zero)
                }
                continue;
            } else {
                // this class has subnets (aka a classless (subnetted) network)
                if ( true === $complete ) {
                    // for all following networks 0 (zero) is expected as (sub)network number
                    if ( 0 !== $subNetwork ) {
                        return $this->_invalidNetwork(__LINE__.':'.++$index);  // NOTE: Index begins at 0 (zero)
                    }
                    continue;
                }
                $complete = true;

                // the (sub)network must be a fact or hosts(/subnets)
                $block = $subNetwork / $hosts;
                if ( is_int($block) ) {
                    // all clear
                    // NOTE: We do NOT return yet because we may have to verify any following segments
                    continue;
                } else {
                    return $this->_invalidNetwork(__LINE__.':'.++$index.':'.$hosts);  // NOTE: Index begins at 0 (zero)
                }
            }
        }

        return true;
    }

    /**
     * Validates a network range with a "from-to" IP address notation
     *
     * @since Version 0.1.36
     * @version 0.1.35 2012/01/16 12:44:00 CST
     * @author aw
     * @desc <p>A network range can be any difference (or equal) between two valid IP addresses. The method will even switch the
     * values if the "to" is lower than the "from" address.</p>
     * @param string $range
     * @return bool
     */
    protected function _validateRange($range) {
        list($from,$to) = explode('-', $range);  // Note: we do NOT care if more IP ranges have been set, i.e. the range would be invalid

        if ( false === ($uInt_from = $this->_makeUnsignedAddress($from)) || false === ($uInt_to = $this->_makeUnsignedAddress($to)) ) {
            return $this->_invalidNetwork(__LINE__);  // at least one of the addresses is not a valid IP address
        }

        if ( $uInt_from <= $uInt_to ) {
            $this->_rangeFrom = $uInt_from;
            $this->_rangeTo   = $uInt_to;
        } else {
            // the range is not in the correct order
            $this->_rangeFrom = $uInt_to;
            $this->_rangeTo   = $uInt_from;
        }

        return true;
    }

    /**
     * Converts an IP address into an unsigned decimal number (see ATTENTION note for returned value)
     *
     * @since Version 0.1.36
     * @version 0.1.35 2012/01/16 12:31:00 CST
     * @author aw
     * @desc <p>Uses php function ip2long() to convert the IP into a signed value first and then returns the value with
     * sprintf($u). ATTENTION: Function sprintf returns this value as a string and typecasting will not produce the expected
     * result for IP addresses above 128.0.0.0. Do not typecast this value to an integer!</p>
     * @param string $ip
     * @return string
     */
    private function _makeUnsignedAddress($ip) {
        if ( false === ($ip_addr_long = ip2long($ip)) ) {
            // not a valid IP address
            return false;
        }

        // Note ip2long creates signed integers
        // a positive number means the address is in the lower half <  128 (0nnn nnnn.)
        // a negative number means the address is in the upper half >= 128 (1nnn nnnn.)
        // 127.255.255.255 =  2147483647
        //       128.0.0.1 = -2147483647
        //       128.0.0.0 = -2147483648

        // convert to unsigned decimal number
        return sprintf('%u',$ip_addr_long);
    }

    /**
     * Triggers an error warning or throws an exception
     *
     * @since Version 0.1.36
     * @version 0.1.36 2012/01/15 11:54:00 CST
     * @author aw
     * @desc <p>The error message contains the argument which is usually the line where the error occured. The calling method
     * may add additional information to the line number.</p>
     * @throws E_USER_WARNING If the _throw property is false (default)
     * @throws Exception If the _throw property is true
     * @param string|int $line
     * @return bool (false)
     */
    private function _invalidNetwork($line) {
        $error_msg = 'The provided network information is not a recognized format [#'.$line.']';
        $this->_error(self::INVALID_NETWORK,$error_msg);
        $msg = '[AWD] Application error: '.$error_msg;
        if ( false === $this->_throw ) {
            trigger_error($msg,E_USER_WARNING);
            return false;
        } else {
            throw new Exception($msg);
        }
    }


}