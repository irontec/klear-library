<?php

/**
 * @author jabi
 * Soporte para HTTP_X_FORWARDED_PROTO
 * ::HTTPS:: >> [Apache Proxy] >> ::HTTP:: >> Apache Base
 * 
 * ProxyRequests Off
 * SSLProxyCheckPeerCN off
 * SSLProxyCheckPeerName off
 * SSLProxyEngine on
 * ProxyPreserveHost On
 * RequestHeader set "X-Forwarded-Proto" "https"
 */
class Iron_View_Helper_ServerUrl extends Zend_View_Helper_ServerUrl
{
    
    public function __construct()
    {
        parent::__construct();
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) {
            $this->setScheme('https');
        }
    }
}