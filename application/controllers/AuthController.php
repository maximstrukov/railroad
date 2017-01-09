<?php

class AuthController extends Zend_Controller_Action {
    
    private $db;

    public function init() {
        /* Initialize action controller here */
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }

    public function indexAction() {
        $this->_helper->redirector('login');
    }

    public function loginAction() {
        
        // проверяем, авторизирован ли пользователь
        if (Zend_Auth::getInstance()->hasIdentity()) {
            // если да, то делаем редирект, чтобы исключить многократную авторизацию
            $this->_helper->redirector('index', 'index');
        }

        // создаём форму и передаём её во view
        $form = new Application_Form_Login();
        $this->view->form = $form;

        // Если к нам идёт Post запрос
        if ($this->getRequest()->isPost()) {
            // Принимаем его
            $formData = $this->getRequest()->getPost();

            // Если форма заполнена верно
            if ($form->isValid($formData)) {
                // Получаем адаптер подключения к базе данных
                $authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());

                // указываем таблицу, где необходимо искать данные о пользователях
                // колонку, где искать имена пользователей,
                // а также колонку, где хранятся пароли
                $authAdapter->setTableName('user')
                    ->setIdentityColumn('username')
                    ->setCredentialColumn('password');

                // получаем введённые данные
                $username = $this->getRequest()->getPost('username');
                $password = $this->getRequest()->getPost('password');

                // подставляем полученные данные из формы
                $authAdapter->setIdentity($username)
                    ->setCredential(md5($password));

                // получаем экземпляр Zend_Auth
                $auth = Zend_Auth::getInstance();

                // делаем попытку авторизировать пользователя
                $result = $auth->authenticate($authAdapter);

                // если авторизация прошла успешно
                if ($result->isValid()) {
                    // используем адаптер для извлечения оставшихся данных о пользователе
                    $identity = $authAdapter->getResultRowObject();
                    
                    // получаем доступ к хранилищу данных Zend
                    $authStorage = $auth->getStorage();

                    // помещаем туда информацию о пользователе,
                    // чтобы иметь к ним доступ при конфигурировании Acl
                    $authStorage->write($identity);
                    
                    if ($formData["remember"]) {
                        $session = md5($username.time());
                        $id = $this->db->query("INSERT INTO `session` SET remote_ip = ?, value = ?",array(
                            ip2long($_SERVER['REMOTE_ADDR']),
                            $session
                        ));
                        $session_id = $this->db->lastInsertId('session');
                        $this->db->query("INSERT INTO user_session SET `user` = ?, `session` = ?",array(
                            $identity->id, 
                            $session_id
                        ));
                        setcookie('railroad_session', $session, time()+60*60*24*365*5, '/');
                    }
                    
                    // Используем библиотечный helper для редиректа
                    // на controller = index, action = index
                    //$this->_helper->redirector('index', 'index');
                    $this->_redirect('/admin');
                } else {
                    $this->view->errMessage = 'Вы ввели неверное имя пользователя или неверный пароль';
                }
            }
        }
    }

    public function logoutAction() {
        
        $auth = Zend_Auth::getInstance();
        //$identity = $auth->getIdentity();
        $request_http = new Zend_Controller_Request_Http();
        $session = $request_http->getCookie('railroad_session');
        //$remote_ip = ip2long($_SERVER['REMOTE_ADDR']);
        $sessions = $this->db->query("SELECT id FROM `session` WHERE value=?",array($session))->fetchAll();
        foreach ($sessions as $session) {
            $this->db->query("DELETE FROM `session` WHERE id=?",array($session['id']));
            $this->db->query("DELETE FROM user_session WHERE `session`=?",array($session['id']));
        }
        
        setcookie("railroad_session", "", time()-3600, '/');
        
        // уничтожаем информацию об авторизации пользователя
        $auth->clearIdentity();

        // и отправляем его на главную
        $this->_redirect('/');
    }


}

