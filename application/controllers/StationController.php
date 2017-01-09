<?php

class StationController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
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
    
    public function searchAction() {
        $q = $this->getRequest()->getParam('q');
        $station = new Application_Model_DbTable_Station;
        if ($this->getRequest()->getParam('filter') != NULL) {
            $config = Zend_Registry::get('config');
            $rows = $station->search($q, null);
            $all_rows = count($rows);
            if ($all_rows > $config->pagesize) {
                $pages = ceil($all_rows/$config->pagesize);
                $rows = $station->search($q, $config->pagesize);
                echo $pages.",".$config->pagesize.",".$config->pages_max_count."\n";
            } else {
                echo "\n";
            }
            foreach ($rows as $row) {
                echo $row->id."|".$row->name."\n";
            }
        } else {
            $rows = $station->search($q);
            foreach ($rows as $row) {
                print $row->name."\n";
            }
        }
        exit;
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

}