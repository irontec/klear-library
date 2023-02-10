<?php
/**
 * @author Mikel Madariaga Madariaga <mikel@irontec.com>
 */

class Iron_Model_Rest_StatusResponse
{
    /**
     * @var int
     */
    protected $_code = 200;

    /**
     * @var string
     */
    protected $_message;

    /**
     * @var array
     */
    protected $_data;

    /**
     * @var Exception
     */
     protected $_exception;
   /**
     * @var string exceptionTrace
     */
     protected $_exceptionTrace;

    /**
     * @var string
     */
    protected $_developerRef;

    /**
     * @var array
     */
    protected $_availableCodes = array();

    private $_successCodes = array(
        200 => 'Ok',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        304 => 'Not Modified'
    );

    private $_clientErrorCodes = array(
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls'
    );

    private $_serverErrorCodes = array(
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended'
    );

    private $_twoWayEncryptPasswd = "IronErrorCryptSecret";

    public function __construct()
    {
        $this->_init();
    }

    protected function _init ()
    {
        $this->_initCodes();
    }

    protected function _initCodes ()
    {

        $this->_availableCodes = $this->_successCodes +
                                 $this->_clientErrorCodes +
                                 $this->_serverErrorCodes;
        $this->setCode(200);

    }

    /**
     * @return bool
     */
    public function anyError()
    {
        return !array_key_exists($this->_code, $this->_successCodes);
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->_code;
    }

    public function setCode($code)
    {

        if (!array_key_exists($code, $this->_availableCodes)) {
            throw new Exception("Unkown error");
        }

        $this->_code = $code;
        $this->setMessage($this->_availableCodes[$code]);

        return $this;

    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function setApplicationError(\Throwable $e)
    {

        $this->_exception = $e;
        $this->_exceptionTrace = $this->_parseAndCleanExceptionTrace($e);

        $this->setCode(500);
        if (array_key_exists($e->getCode(), $this->_availableCodes)) {
            $this->setCode($e->getCode());
        }

        $this->_developerRef = $e->getFile() . "(". $e->getLine() .")";

    }

    /**
     * @return string
     */
    protected function _parseAndCleanExceptionTrace(\Throwable $e)
    {

        $trace = $e->getTrace();
        $cleanTrace = array();

        foreach ($trace as $key => $item) {

            if (!isset($item['file'])) {
                continue;
            }

            if ($key !== 0 && strpos($item['file'], "Zend") !== false) {
                continue;
            }

            $cleanTrace[] = $item;
        }

        $itemNum = count($cleanTrace) -1;
        unset($cleanTrace[$itemNum]);

        foreach ($cleanTrace as $key => $item) {
            if ($key != ($itemNum-1)) {
                unset($cleanTrace[$key]['args']);
            }
        }

        $cleanTraceString = "";
        foreach ($cleanTrace as $key => $item) {

           $str = "";
           $str .= "#" . $key . " " . $item['file'] . "(". $item['line'] ."): ";
           $str .= $item['class'] . $item['type'] . $item['function'] ."()";
           $cleanTraceString .= $str . "\n";
        }


        $request = Zend_Controller_Front::getInstance()->getRequest();
        $simpleArguments = array();
        foreach ($request->getParams() as $key => $param) {
            if (is_object($param)) {
                continue;
            }
            $simpleArguments[$key] = $param;
        }

        $arguments = "\n>> Arguments >> \n" . var_export($simpleArguments, true);

        return $e->getMessage() . "\n" .
               $cleanTraceString . "\n" .
               $arguments;

    }

    public function setMessage($message)
    {
        $this->_message = $message;
        return $this;
    }

    public function getException()
    {

        $response = array();

        if ($this->_exception instanceof \Exception) {

            $exceptionCode = $this->_exception->getCode();
            if ($exceptionCode != 0 && $exceptionCode != $this->_code) {
                $response +=  array(
                    'exceptionCode' => $this->_exception->getCode(),
                );
            }

            $cleanDevRef = $this->_developerRef . "\n" . $this->_exceptionTrace;
            $devRef = $this->_developerRefEncrypt($cleanDevRef);

            $response += array(
                'exception' => $this->_exception->getMessage(),
                'developerRef' => $devRef
            );

        }

        return $response;

    }

    protected function _developerRefEncrypt($string)
    {

        if (!function_exists('mcrypt_decrypt')) {
            return '';
        }

        if (false && !in_array(APPLICATION_ENV, array('production', 'testing'))) {
            return $string;
        }

        $hash = mcrypt_encrypt(
            MCRYPT_RIJNDAEL_256,
            md5($this->_twoWayEncryptPasswd),
            $string,
            MCRYPT_MODE_CBC,
            md5(md5($this->_twoWayEncryptPasswd))
        );

        return urlencode(base64_encode($hash));

    }

    public function uncryptDeveloperRefMessage($encrypted)
    {

        if (!function_exists('mcrypt_decrypt')) {
            return $encrypted;
        }

        $string = mcrypt_decrypt(
            MCRYPT_RIJNDAEL_256,
            md5($this->_twoWayEncryptPasswd),
            base64_decode(urldecode($encrypted)),
            MCRYPT_MODE_CBC,
            md5(md5($this->_twoWayEncryptPasswd))
        );

        return rtrim($string, "\0");

    }

}