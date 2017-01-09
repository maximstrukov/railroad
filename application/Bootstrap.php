<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initDoctype() {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('HTML5');
    }

    protected function _initRouter() {
        $front = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/router.ini', 'production');
        $router->addConfig($config,'routes');
    }
    protected function _initAcl() {
        // Создаём объект Zend_Acl
        //require_once 'Zend/Acl/Role.php';
        $acl = new Zend_Acl();

        // Добавляем ресурсы нашего сайта,
        // другими словами указываем контроллеры и действия

        // указываем, что у нас есть ресурс index
        $acl->addResource('index');
        $acl->addResource('train');
        $acl->addResource('station');
        $acl->addResource('youtube');
        /*
        // ресурс add является потомком ресурса index
        $acl->addResource('add', 'index');

        // ресурс edit является потомком ресурса index
        $acl->addResource('edit', 'index');

        // ресурс delete является потомком ресурса index
        $acl->addResource('delete', 'index');*/
        
        // указываем, что у нас есть ресурс error
        $acl->addResource('error');

        // указываем, что у нас есть ресурс auth
        $acl->addResource('auth');

        // ресурс login является потомком ресурса auth
        $acl->addResource('login', 'auth');

        // ресурс logout является потомком ресурса auth
        $acl->addResource('logout', 'auth');

        $acl->addResource('admin_index');
        $acl->addResource('admin_station');
        $acl->addResource('admin_train');
        $acl->addResource('admin_youtube');
        
        // далее переходим к созданию ролей, которых у нас 2:
        // гость (неавторизированный пользователь)
        $acl->addRole('guest');

        // администратор, который наследует доступ от гостя
        $acl->addRole('admin', 'guest');

        // разрешаем гостю просматривать ресурс index
        $acl->allow('guest', 'index', array('index'));
        $acl->allow('guest', 'train', array('index','search','view','route'));
        $acl->allow('guest', 'station', array('index','search','view'));
        $acl->allow('guest', 'youtube', array('index'));
        // разрешаем гостю просматривать ресурс auth и его подресурсы
        $acl->allow('guest', 'auth', array('index', 'login', 'logout'));

        // даём администратору доступ к ресурсам 'add', 'edit' и 'delete'
        //$acl->allow('admin', 'index', array('add', 'edit', 'delete'));

        // разрешаем администратору просматривать страницу ошибок
        $acl->allow('admin', 'error');
        $acl->allow('admin', 'admin_index', array('index'));
        $acl->allow('admin', 'admin_station', array('index','view','edit','add','delete'));
        $acl->allow('admin', 'admin_train', array('index','view','edit','add','delete'));
        $acl->allow('admin', 'admin_youtube', array('index','add','delete','save'));

        // получаем экземпляр главного контроллера
        $front = Zend_Controller_Front::getInstance();

        // регистрируем плагин с названием AccessCheck, в который передаём
        // на ACL и экземпляр Zend_Auth
        $front->registerPlugin(new Application_Plugin_AccessCheck($acl, Zend_Auth::getInstance()));
        
    }
    
    protected function _initLoginForm() {
        $form = new Application_Form_Login();
        Zend_Registry::set('login_form',$form);
    }
    
    protected function _initConfig() {
        $config = new Zend_Config($this->getOptions(), true);
        Zend_Registry::set('config', $config);
        return $config;
    }

}