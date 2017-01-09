<?php

class Application_Plugin_AccessCheck extends Zend_Controller_Plugin_Abstract {

    private $_acl = null;
    private $_auth = null;

    /*
     * Инициализируем данные
     */

    public function __construct(Zend_Acl $acl, Zend_Auth $auth) {
        $this->_acl = $acl;
        $this->_auth = $auth;
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        
        // получаем имя текущего ресурса
        $resource = $request->getControllerName();
        
        // имя текущего модуля
        $module = $request->getModuleName();
        if ($module=='admin') $resource = 'admin_'.$resource;
        
        // получаем имя action
        $action = $request->getActionName();

        $request_http = new Zend_Controller_Request_Http();
        $session = $request_http->getCookie('railroad_session');
        if ($session) {
            $db = Zend_Db_Table::getDefaultAdapter();
            $row = $db->query("SELECT u.* FROM `user` u JOIN user_session us ON u.id=us.user JOIN `session` s ON us.`session`=s.id WHERE s.value='".$session."'")->fetch();
            if ($row) {
                $authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());
                $authAdapter->setTableName('user')
                    ->setIdentityColumn('username')
                    ->setCredentialColumn('password');
                $authAdapter->setIdentity($row['username'])
                    ->setCredential($row['password']);
                $auth = Zend_Auth::getInstance();
                $result = $auth->authenticate($authAdapter);
                $identity = $authAdapter->getResultRowObject();
                $authStorage = $auth->getStorage();
                $authStorage->write($identity);
                $role = $row['role'];
                //$route = Zend_Controller_Front::getInstance()->getRouter()->getCurrentRouteName();
            } else {
                $role = 'guest';
            }
        } else {
            // получаем доступ к хранилищу данных Zend,
            // и достаём роль пользователя
            $identity = $this->_auth->getStorage()->read();
            $role = !empty($identity->role) ? $identity->role : 'guest';
        }
        // если в хранилище ничего нет, то значит мы имеем дело с гостем
        //$role = !empty($user_role) ? $user_role : 'guest';
        
        if ($role == 'admin') {
            $module = 'admin';
        } else {
            Zend_Auth::getInstance()->clearIdentity();
            $module = 'default';
        }
        Zend_Registry::set('module', $module);
        // если пользователь не допущен до данного ресурса,
        // то отсылаем его на страницу авторизации 
        if (!$this->_acl->isAllowed($role, $resource, $action)) {
            $request->setControllerName('auth')->setActionName('index');
        }
    }

}