<?php
require_once 'Zend/Mail/Protocol/Smtp.php';
class Zend_Mail_Protocol_Smtp_Auth_Xoauth2 extends Zend_Mail_Protocol_Smtp
{
    protected $_xoauth_request;
    public function __construct($host = '127.0.0.1', $port = null, $config = null)
    {
        if (is_array($config)) {
            if (isset($config['xoauth_request'])) {
                $this->_xoauth_request = $config['xoauth_request'];
            }
        }
        parent::__construct($host, $port, $config);
    }

    public function auth()
    {
        // Ensure AUTH has not already been initiated.
        parent::auth();
        $this->_send('AUTH XOAUTH2 '. $this->_xoauth_request);
        $this->_expect(235);
        $this->_auth = true;
    }
}
