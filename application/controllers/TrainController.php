<?php

class TrainController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
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
            $train = new Application_Model_DbTable_Train();
            $this_train = $train->fetchRow("id=".$id);
            $train_class = new Application_Model_Train;
            $train_class->getVars($this_train);
            $this->view->entry = $train_class;
        }
    }
    
    public function searchAction() {
        $q = $this->getRequest()->getParam('q');
        $train = new Application_Model_DbTable_Train;
        $config = Zend_Registry::get('config');
        $rows = $train->search($q, null);
        $all_rows = count($rows);
        if ($all_rows > $config->pagesize) {
            $pages = ceil($all_rows/$config->pagesize);
            $rows = $train->search($q, $config->pagesize);
            echo $pages.",".$config->pagesize.",".$config->pages_max_count."\n";
        } else {
            echo "\n";
        }
        foreach ($rows as $row) {
            //$train_class = new Application_Model_Train;
            //$train_class->getVars($row);
            $output = $row->id."|".$row->number."|".$row->period."|";
            $output .= $row->station_from_id."|".$row->station_from_name."|".$row->departure."|";
            $output .= $row->station_to_id."|".$row->station_to_name."|".$row->arrival;
            $output .= "\n";
            echo $output;
        }
        exit;
    }
    
    public function routeAction() {
        $this->view->headScript()->appendFile('/js/jquery.autocomplete.js');
        $this->view->headScript()->appendFile('/js/train.js');
        $request = $this->getRequest()->getParams();
        $rows = array();
        if (isset($request['st_from']) && isset($request['st_to'])) {
            if (!empty($request['st_from']) && !empty($request['st_to'])) {
                $module = Zend_Registry::get('module');
                $train_class = new Application_Model_Train;
                $rows = $train_class->searchRoute($request['st_from'], $request['st_to']);
                echo json_encode(array($module,$rows));
            }
            exit;
        }
    }
    
}