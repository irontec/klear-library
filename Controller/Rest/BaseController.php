<?php

class Iron_Controller_Rest_BaseController extends \Zend_Rest_Controller
{

    public $status;
    public $syslog;

    public function init()
    {

        $bootstrap = $this->_invokeArgs['bootstrap'];

        if (!Zend_Registry::isRegistered("syslogger")) {

            $writer = new Zend_Log_Writer_Syslog(
                array(
                    'application' => $bootstrap->getAppNamespace() . '-Rest'
                )
            );

            Zend_Registry::set("syslogger", new Zend_Log($writer));

        }

        $this->_debugParams();
        $this->status = new \Iron_Model_Rest_StatusResponse;

        $this->syslog = Zend_Registry::get("syslogger");

        if (get_class($this) != "Api_ErrorController") {
            $this->_logRequest();
        }

    }

    private function _logRequest()
    {

        $module = $this->_request->getParam("module");
        $controller = $this->_request->getParam("controller");
        $action = $this->_request->getParam("action");

        $requestLog = $module . "/" . $controller . "::". $action;

        $params = $this->_request->getParams();

        foreach (array('module', 'controller', 'action') as $key) {
            unset($params[$key]);
        }

        $requestParamString = var_export($params, true);

        $requestLog .= " from " . $_SERVER["REMOTE_ADDR"];

        $this->syslog->debug(
            "Requesting " . $requestLog
        );

        $resquestParams = str_replace("\n", "", $requestParamString);

        $this->syslog->debug(
            "Request params: " . $resquestParams
        );

    }

    private function _logResponse()
    {

        $statusResume = $this->status->getStatusArray();

        if (array_key_exists('exception', $statusResume)) {

            $msg = "Exception thrown: " . $statusResume['exception'];
            $this->syslog->debug($msg);

            $msg = "Exception Ref: " . $statusResume['developerRef'];
            $this->syslog->debug($msg);

        }

        $msg = "Request finished with status code " . $this->status->getCode();
        $msg .= " [" . $this->status->getMessage() . "]";
        $this->syslog->debug($msg);

    }

    public function preDispatch()
    {

        $contextSwitch = $this->getHelper("contextSwitch");
        $contextSwitch
                ->addActionContext('index', 'json')
                ->addActionContext('error', 'json')
                ->addActionContext('get', 'json')
                ->addActionContext('post', 'json')
                ->addActionContext('head', 'json')
                ->addActionContext('put', 'json')
                ->addActionContext('delete', 'json')
                ->addActionContext('options', 'json')
                ->initContext('json');

    }

    public function postDispatch()
    {

        $this->_responseContent();

        if (
            $this->_request->getUserParam("controller") !=
            $this->_request->getControllername() &&
            (get_class($this) != "Api_ErrorController")
        ) {
            return;
        }

        $this->_logResponse();

    }

    protected function _responseContent()
    {

        $this->getResponse()->setHttpResponseCode(
            $this->status->getCode()
        );

        $this->getResponse()->setHeader(
            'Content-type',
            'application/json; charset=UTF-8;'
        );

        $view = $this->view;

        if ($this->status->anyError()) {
            $debug = $view->debug;
            $view->clearVars();

            if ($debug) {
                $view->debug = $debug;
            }
        }

        foreach ($this->status->getStatusArray() as $key => $val) {
            $view->$key = $val;
        }

    }

    protected function _debugParams($ignoreEnviroment = false)
    {

        $env = array(
            'production',
            'testing'
        );

        if ($ignoreEnviroment || !in_array(APPLICATION_ENV, $env)) {

            $requestParams = $this->_request->getParams();

            if (array_key_exists('error_handler', $requestParams)) {
                unset($requestParams['error_handler']);
            }

            $this->view->debug = $requestParams;

        }

    }

    /**
     * The index action handles index/list requests; it should respond with a
     * list of the requested resources.
     */
    public function indexAction()
    {
        $this->_methodNotAllowed();
    }

    /**
     * The get action handles GET requests and receives an 'id' parameter; it
     * should respond with the server resource state of the resource identified
     * by the 'id' value.
     */
    public function getAction()
    {
        $this->_methodNotAllowed();
    }

    /**
     * The head action handles HEAD requests and receives an 'id' parameter; it
     * should respond with the server resource state of the resource identified
     * by the 'id' value.
     */
    public function headAction()
    {
        $this->_methodNotAllowed();
    }

    /**
     * The post action handles POST requests; it should accept and digest a
     * POSTed resource representation and persist the resource state.
     */
    public function postAction()
    {
        $this->_methodNotAllowed();
    }

    /**
     * The put action handles PUT requests and receives an 'id' parameter; it
     * should update the server resource state of the resource identified by
     * the 'id' value.
     */
    public function putAction()
    {
        $this->_methodNotAllowed();
    }

    /**
     * The delete action handles DELETE requests and receives an 'id'
     * parameter; it should update the server resource state of the resource
     * identified by the 'id' value.
     */
    public function deleteAction()
    {
        $this->_methodNotAllowed();
    }

    public function optionsAction()
    {
        $this->_methodNotAllowed();
    }

    private function _methodNotAllowed()
    {
        $this->status->setCode(405);
    }

}