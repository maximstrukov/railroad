<?php

class Admin_StationController extends Zend_Controller_Action {

    public function init() {
        //$this->_helper->layout->setLayout('admin');
    }

    public function indexAction() {
        
        $this->view->headScript()->appendFile('/js/station.js');
        
        $request = $this->getRequest()->getParams();
        if (isset($request["page"])) $page = $request["page"];
        else $page = 1;
        $stations = new Application_Model_DbTable_Station();
        $select = $stations->select();
        if (isset($request["q"])) {
            $q = $request["q"];
            $select = $select->where('`name` LIKE "'.$q.'%"');
        } else {
            $q = '';
        }
        $config = Zend_Registry::get('config');
        $all_rows = count($stations->fetchAll($select));
        if ($all_rows > $config->pagesize) {
            $pages = ceil($all_rows/$config->pagesize);
            if ($page < 1 || $page > $pages) $page = 1;
            $select = $select->order('name')->limit($config->pagesize, ($page-1)*$config->pagesize);
            $rows = $stations->fetchAll($select);
        } else {
            $pages = 1;
            $select = $select->order('name');
            $rows = $stations->fetchAll($select);
        }
        
        $this->view->stations = $rows;
        $this->view->pages = $pages;
        $this->view->page = $page;
        $this->view->pagesize = $config->pagesize;
        $this->view->pages_max_count = $config->pages_max_count;
        $this->view->query = $q;
    }

    public function addAction() {
        
        $this->view->headScript()->appendFile('/js/station.js');
        
        // Создаём форму
        $form = new Application_Form_Station();

        // Указываем текст для submit
        $form->submit->setLabel('Сохранить');

        // Передаём форму в view
        $this->view->form = $form;

        // Если к нам идёт Post запрос
        if ($this->getRequest()->isPost()) {
            // Принимаем его
            $formData = $this->getRequest()->getPost();

            // Если форма заполнена верно
            if ($form->isValid($formData)) {
                
                $name = $form->getValue('name');
                $timezone = $form->getValue('timezone');
                $note = $form->getValue('note');
                
                // Создаём объект модели
                $station = new Application_Model_DbTable_Station();

                // Вызываем метод модели addStation для вставки новой записи
                $station->addStation($name, $timezone, $note);
                
                $names = $formData["names"];
                $timezones = $formData["timezones"];
                $notes = $formData["notes"];
                
                foreach ($names as $n => $name) {
                    $name = trim($name);
                    $timezone = $timezones[$n];
                    $note = $notes[$n];
                    if (!empty($name)) $station->addStation($name, $timezone, $note);
                }
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
    
    public function viewAction() {
        $this->view->headScript()->appendFile('https://maps.google.com/maps/api/js?sensor=false');
        $id = $this->_getParam('id', 0);
        if ($id > 0) {
            $model = new Application_Model_DbTable_Station();
            $station = $model->getStation($id);
            $stop_model = new Application_Model_DbTable_Stop();
            $select = $stop_model->select()->where("station=".$id)->order('arrival');
            $stops = $stop_model->fetchAll($select);
            $trains = array();
            $train = new Application_Model_DbTable_Train();
            foreach ($stops as $stop) {
                $this_train = $train->fetchRow("id=".$stop->train);
                $train_class = new Application_Model_Train;
                $train_class->getVars($this_train);
                $trains[] = array(
                    "train_id"=>$train_class->id,
                    "train_number"=>$train_class->number,
                    "from_station_id"=>$train_class->stops[0]["station_id"],
                    "from_station_name"=>$train_class->stops[0]["station_name"],
                    "to_station_id"=>$train_class->stops[count($train_class->stops)-1]["station_id"],
                    "to_station_name"=>$train_class->stops[count($train_class->stops)-1]["station_name"],
                    "arrival"=>$stop->arrival,
                    "departure"=>$stop->departure,
                    "period"=>$train_class->period,
                    "last_arrival"=>$train_class->stops[count($train_class->stops)-1]["arrival"]
                );
            }
            
            $this->view->id = $id;
            $this->view->name = $station["name"];
            $this->view->lat = $station["lat"];
            $this->view->lng = $station["lng"];
            $this->view->trains = $trains;
            $timezones = new Application_Model_DbTable_Timezone();
            $row = $timezones->fetchRow('id='.$station["timezone"]);
            $this->view->timezone = $row;
        }
    }
    
    public function editAction() {
        // Создаём форму
        $form = new Application_Form_Station();

        // Указываем текст для submit
        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        // Если к нам идёт Post запрос
        if ($this->getRequest()->isPost()) {
            // Принимаем его
            $formData = $this->getRequest()->getPost();

            // Если форма заполнена верно
            if ($form->isValid($formData)) {
                // Извлекаем id
                $id = (int)$form->getValue('id');

                $name = $form->getValue('name');
                $timezone = $form->getValue('timezone');
                $note = $form->getValue('note');

                // Создаём объект модели
                $station = new Application_Model_DbTable_Station();

                // Вызываем метод модели updateStation для обновления новой записи
                $station->updateStation($id, $name, $timezone, $note);

                // Используем библиотечный helper для редиректа на action = index
                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);
            }
        } else {
            // Если мы выводим форму, то получаем id станции, которую хотим обновить
            $id = $this->_getParam('id', 0);
            if ($id > 0) {
                // Создаём объект модели
                $station = new Application_Model_DbTable_Station();

                // Заполняем форму информацией при помощи метода populate
                $form->populate($station->getStation($id));
            }
        }
        $this->view->id = $id;
    }
    
    public function deleteAction() {
        // Принимаем id записи, которую хотим удалить
        $id = $this->getRequest()->getParam('id');
        if ($id != NULL) {
            // Создаём объект модели
            $station = new Application_Model_DbTable_Station();

            // Вызываем метод модели deleteStation для удаления записи
            $station->deleteStation($id);
        }
        // Используем библиотечный helper для редиректа на action = index
        $this->_helper->redirector('index');
    }

}