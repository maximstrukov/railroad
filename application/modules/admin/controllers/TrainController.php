<?php

class Admin_TrainController extends Zend_Controller_Action {

    public function init() {
        //$this->_helper->layout->setLayout('admin');
    }

    public function indexAction() {
        $this->view->headScript()->appendFile('/js/train.js');
        
        $request = $this->getRequest()->getParams();
        if (isset($request["page"])) $page = $request["page"];
        else $page = 1;
        
        $train_model = new Application_Model_DbTable_Train();
        $select = $train_model->select();
        
        if (isset($request["q"])) {
            $q = $request["q"];
            $select = $select->where('`number` LIKE "'.$q.'%"');
        } else {
            $q = '';
        }
        
        $config = Zend_Registry::get('config');
        
        $rows_count = count($train_model->fetchAll($select));
        if ($rows_count > $config->pagesize) {
            $pages = ceil($rows_count/$config->pagesize);
            if ($page < 1 || $page > $pages) $page = 1;
            $select = $select->order('number')->limit($config->pagesize, ($page-1)*$config->pagesize);
            $rows = $train_model->fetchAll($select);
        } else {
            $pages = 1;
            $select = $select->order('number');
            $rows = $train_model->fetchAll($select);
        }
        
        $data = array();
        foreach ($rows as $train) {
            $train_class = new Application_Model_Train;
            $train_class->getVars($train);
            $data[] = $train_class;
        }
        
        $this->view->trains = $data;
        $this->view->pages = $pages;
        $this->view->page = $page;
        $this->view->pagesize = $config->pagesize;
        $this->view->pages_max_count = $config->pages_max_count;
        $this->view->query = $q;
    }

    public function viewAction() {
        $this->view->headScript()->appendFile('https://maps.google.com/maps/api/js?sensor=false');
        $id = $this->_getParam('id', 0);
        if ($id > 0) {
            // Создаём объект модели
            $train = new Application_Model_DbTable_Train();
            $this_train = $train->fetchRow("id=".$id);
            $train_class = new Application_Model_Train;
            $train_class->getVars($this_train);
            $this->view->entry = $train_class;
        }
    }
    
    public function addAction() {
        
        $this->view->headScript()->appendFile('/js/jquery.autocomplete.js');
        $this->view->headScript()->appendFile('/js/jquery.tablednd_0_5.js');
        $this->view->headScript()->appendFile('/js/train.js');
        
        // Создаём форму
        $form = new Application_Form_Train();

        // Указываем текст для submit
        $form->submit->setLabel('Сохранить');

        // Передаём форму в view
        $this->view->form = $form;                
        
        // Если к нам идёт Post запрос
        if ($this->getRequest()->isPost()) {
            // Принимаем его
            $formData = $this->getRequest()->getPost();

            $isValid = $form->isValid($formData);
            $elements = $form->getElements();
            
            // Если форма заполнена верно
            if ($isValid && !$form->stops_errors) {

                $number = $form->getValue('number');
                $period = $form->getValue('period');

                $station_from_text = trim($formData["station_from"]);
                $station = new Application_Model_DbTable_Station();
                $timezone_from = $formData["timezone_from"];
                $station_from = $station->addStation($station_from_text, $timezone_from);
                
                $station_to_text = trim($formData["station_to"]);
                //$station = new Application_Model_DbTable_Station();
                $timezone_to = $formData["timezone_to"];
                $station_to = $station->addStation($station_to_text, $timezone_to);
                
                $time_from = $form->getValue('time_from');
                $time_to = $form->getValue('time_to');
                
                
                // Создаём объект модели
                $train = new Application_Model_DbTable_Train();

                // Вызываем метод модели addTrain для вставки новой записи
                $id = $train->addTrain($number, $period);
                
                $stop = new Application_Model_DbTable_Stop();
                $stop->addStop($station_from, $id, '', $time_from, 1);
                $last_stop = 2;
                
                //Adding way stations
                if (isset($formData["stops"])) {
                    foreach ($formData["stops"] as $s => $way_stop_text) {
                        $way_stop_text = trim($way_stop_text);
                        if (!empty($way_stop_text)) {
                            $station = new Application_Model_DbTable_Station();
                            $way_station = $station->addStation($way_stop_text, $formData["timezone"][$s]);
                        }
                        if ($way_station) {
                            $stop->addStop($way_station, $id, $formData["arrival"][$s], $formData["departure"][$s], ($s+2));
                        }
                    }
                    $last_stop = count($formData["stops"])+2;
                }
                
                $stop->addStop($station_to, $id, $time_to, '', $last_stop);
                
                // Используем библиотечный helper для редиректа на action = index
                $this->_helper->redirector('index');
                //$this->_redirect('/admin');
            } else {
                // Если форма заполнена неверно,
                // используем метод populate для заполнения всех полей
                // той информацией, которую ввёл пользователь

                $form->populate($formData);
            }
        }
    }
    
    public function editAction() {
        
        $this->view->headScript()->appendFile('/js/jquery.autocomplete.js');
        $this->view->headScript()->appendFile('/js/jquery.tablednd_0_5.js');
        $this->view->headScript()->appendFile('/js/train.js');
        
        $form = new Application_Form_Train();

        $form->submit->setLabel('Сохранить');

        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();

            $isValid = $form->isValid($formData);
            $elements = $form->getElements();
            $id = $form->getValue('id');
            if ($isValid && !$form->stops_errors && !$form->number_error) {

                $number = $form->getValue('number');
                $period = $form->getValue('period');

                $station_from_text = trim($formData["station_from"]);
                $station = new Application_Model_DbTable_Station();
                $timezone_from = $formData["timezone_from"];
                $station_from = $station->addStation($station_from_text, $timezone_from);
                
                $station_to_text = trim($formData["station_to"]);
                $station = new Application_Model_DbTable_Station();
                $timezone_to = $formData["timezone_to"];
                $station_to = $station->addStation($station_to_text, $timezone_to);
                $time_from = $form->getValue('time_from');
                $time_to = $form->getValue('time_to');
                
                $train = new Application_Model_DbTable_Train();

                $train->updateTrain($id, $number, $period);
                $train->deleteTrainStops($id);
                
                $stop = new Application_Model_DbTable_Stop();
                $stop->addStop($station_from, $id, '', $time_from, 1);
                $last_stop = 2;
                
                //Adding way stations
                if (isset($formData["stops"])) {
                    foreach ($formData["stops"] as $s => $way_stop_text) {
                        $way_stop_text = trim($way_stop_text);
                        if (!empty($way_stop_text)) {
                            $station = new Application_Model_DbTable_Station();
                            $way_station = $station->addStation($way_stop_text, $formData["timezone"][$s]);
                        }
                        if ($way_station) {
                            $stop->addStop($way_station, $id, $formData["arrival"][$s], $formData["departure"][$s], ($s+2));
                            $way_station = false;
                        }
                    }
                    $last_stop = count($formData["stops"])+2;
                }
                $stop->addStop($station_to, $id, $time_to, '', $last_stop);
                $this->_helper->redirector('index');
                //$this->_redirect('/admin');
            } else {
                $form->populate($formData);
            }            

        } else {
            // Если мы выводим форму, то получаем id станции, которую хотим обновить
            $id = $this->_getParam('id', 0);
            if ($id > 0) {
                // Создаём объект модели
                $train = new Application_Model_DbTable_Train();
                // Заполняем форму информацией при помощи метода populate
                $data = $train->getTrain($id);
                $this_train = $train->fetchRow("id=".$id);
                $train_class = new Application_Model_Train;
                $train_class->getVars($this_train);
                $data["station_from"] = $train_class->stops[0]["station_name"];
                $data["station_to"] = $train_class->stops[count($train_class->stops)-1]["station_name"];
                $data["time_from"] = $train_class->stops[0]["departure"];
                $data["time_to"] = $train_class->stops[count($train_class->stops)-1]["arrival"];
                $form->populate($data);
            }
        }
        $this->view->id = $id;
    }
    
    public function deleteAction() {
        // Принимаем id записи, которую хотим удалить
        $id = $this->getRequest()->getParam('id');
        if ($id != NULL) {
            // Создаём объект модели
            $train = new Application_Model_DbTable_Train();

            // Вызываем метод модели deleteStation для удаления записи
            $train->deleteTrain($id);
            
        }
        // Используем библиотечный helper для редиректа на action = index
        $this->_helper->redirector('index');
    }
    
}