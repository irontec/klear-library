<?php
/**
 * @author Mikel Madariaga Madariaga <mikel@irontec.com>
 * @author Daniel Rendon <dani@irontec.com>
 */
class Iron_Controller_Rest_BaseController extends \Zend_Rest_Controller {
    public $status;
    public $logger;
    protected $_viewData;
    public function init() {
        $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $this->logger = $bootstrap->getResource('log');
       
        $plugins = $bootstrap->getContainer ()->frontcontroller->getPlugins ();
        
        $this->_checkPluginInit ( $plugins );
        
        $this->status = new \Iron_Model_Rest_StatusResponse ();
        
        $front = \Zend_Controller_Front::getInstance ();
        $request = $front->getRequest ();
        
        if ($request->getActionName () != "rest-error") {
            $this->_logRequest ();
        }
        
        $errorHandler = $front->getPlugin ( 'Zend_Controller_Plugin_ErrorHandler' );
        $errorHandler->setErrorHandlerAction ( 'rest-error' )->setErrorHandlerController ( $request->getControllerName () )->setErrorHandlerModule ( $request->getModuleName () );
    }
    public function location() {
        $location = $this->view->serverUrl ( $this->view->url () );
        
        return $location;
    }
    public function restErrorAction() {
        $errors = $this->_getParam ( 'error_handler' );
        
        if (! $errors || ! $errors instanceof ArrayObject) {
            $this->view->message = 'You have reached the error page';
            return;
        }
        
        $this->status->setApplicationError ( $errors->exception );
        $this->view->error = $errors->exception->getMessage ();
    }

    protected function _checkPluginInit($plugins) {
        $init = false;
        
        foreach ( $plugins as $plugin ) {
            if (get_class ( $plugin ) === 'Iron_Plugin_RestParamsParser') {
                $init = true;
            }
        }
        
        if (! $init) {
            throw new Exception ( 'No esta inicializado el plugin "Iron_Plugin_RestParamsParser"' );
        }
    }
    private function _logRequest() {
        $module = $this->_request->getParam ( "module" );
        $controller = $this->_request->getParam ( "controller" );
        $action = $this->_request->getParam ( "action" );
        
        $requestLog = $module . "/" . $controller . "::" . $action;
        
        $params = $this->_request->getParams ();
        
        foreach ( array (
                'module',
                'controller',
                'action' 
        ) as $key ) {
            unset ( $params [$key] );
        }
        
        $requestParamString = var_export ( $params, true );
        
        $requestLog .= " from " . $_SERVER ["REMOTE_ADDR"];
        
        $this->logger->debug ( "api-rest  Requesting " . $requestLog );
        
        $resquestParams = str_replace ( "\n", "", $requestParamString );
        
        $this->logger->debug ( "api-rest  Request params: " . $resquestParams );
    }
    private function _logResponse() {
        $statusResume = $this->status->getException ();
        
        if (array_key_exists ( 'exception', $statusResume )) {
            
            $msg = "Exception thrown: " . $statusResume ['exception'];
            $this->logger->debug ("api-rest  " . $msg );
            
            $msg = "Exception Ref: " . $statusResume ['developerRef'];
            $this->logger->debug ("api-rest  " . $msg );
        }
        
        
        $msg = "Request finished with status code " . $this->status->getCode ();
        $msg .= " [" . $this->status->getMessage () . "]";
        $this->logger->debug ("api-rest  " . $msg );
    }
    public function preDispatch() {
        $contextSwitch = $this->getHelper ( "contextSwitch" );
        $contextSwitch->addActionContext ( 'index', 'json' )->addActionContext ( 'error', 'json' )->addActionContext ( 'rest-error', 'json' )->addActionContext ( 'get', 'json' )->addActionContext ( 'post', 'json' )->addActionContext ( 'head', 'json' )->addActionContext ( 'put', 'json' )->addActionContext ( 'delete', 'json' )->addActionContext ( 'options', 'json' )->initContext ( 'json' );
    }
    public function postDispatch() {
        $this->_responseContent ();
        
        if ($this->_request->getUserParam ( "controller" ) != $this->_request->getControllername () && (get_class ( $this ) != "Api_ErrorController")) {
            return;
        }
        $this->_logResponse ();
    }
    protected function _responseContent() {
        $this->getResponse ()->setHttpResponseCode ( $this->status->getCode () );
        
        $this->getResponse ()->setHeader ( 'Content-type', 'application/json; charset=UTF-8;' );
        
        $view = $this->view;
        
        $exceptionData = $this->status->getException ();
        if (! empty ( $exceptionData )) {
            $exceptionEncode = json_encode ( $exceptionData );
            $this->getResponse ()->setHeader ( 'exception', $exceptionEncode );
        }
        
        $dataView = $this->_viewData;
        if (! empty ( $dataView )) {
            foreach ( $dataView as $key => $val ) {
                $view->$key = $val;
            }
        }
    }
    public function addViewData($data) {
        $this->_viewData = $data;
    }
    
    /**
     * The index action handles index/list requests; it should respond with a
     * list of the requested resources.
     */
    public function indexAction() {
        $this->_methodNotAllowed ();
    }
    
    /**
     * The get action handles GET requests and receives an 'id' parameter; it
     * should respond with the server resource state of the resource identified
     * by the 'id' value.
     */
    public function getAction() {
        $this->_methodNotAllowed ();
    }
    
    /**
     * The head action handles HEAD requests and receives an 'id' parameter; it
     * should respond with the server resource state of the resource identified
     * by the 'id' value.
     */
    public function headAction() {
        $this->_methodNotAllowed ();
    }
    
    /**
     * The post action handles POST requests; it should accept and digest a
     * POSTed resource representation and persist the resource state.
     */
    public function postAction() {
        $this->_methodNotAllowed ();
    }
    
    /**
     * The put action handles PUT requests and receives an 'id' parameter; it
     * should update the server resource state of the resource identified by
     * the 'id' value.
     */
    public function putAction() {
        $this->_methodNotAllowed ();
    }
    
    /**
     * The delete action handles DELETE requests and receives an 'id'
     * parameter; it should update the server resource state of the resource
     * identified by the 'id' value.
     */
    public function deleteAction() {
        $this->_methodNotAllowed ();
    }
    public function optionsAction() {
        $this->_methodNotAllowed ();
    }
    private function _methodNotAllowed() {
        $this->status->setCode ( 405 );
    }
}