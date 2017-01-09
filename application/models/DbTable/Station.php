<?php

class Application_Model_DbTable_Station extends Zend_Db_Table_Abstract {

    // Имя таблицы, с которой будем работать
    protected $_name = 'station';

    protected $_dependentTables = array('Stop');
    
    protected $_referenceMap    = array(
        'Timezone' => array(
            'columns'           => 'timezone',
            'refTableClass'     => 'Application_Model_DbTable_Timezone',
            'refColumns'        => 'id'
        )
    );    
    
    // Метод для получения записи по id
    public function getStation($id) {
        // Получаем id как параметр
        $id = (int) $id;

        // Используем метод fetchRow для получения записи из базы.
        // В скобках указываем условие выборки (привычное для вас where)
        $row = $this->fetchRow('id = ' . $id);

        // Если результат пустой, выкидываем исключение
        if (!$row) {
            throw new Exception("Нет записи с id - $id");
        }
        
        if (empty($row->lat) || empty($row->lng)) {
            $address = $row->name;
            if (!empty($row->note)) $address .= ",".$row->note;
            $loc = Application_Model_Geocoder::getLocation($address);
            $station_model = new Application_Model_DbTable_Station;
            $station_model->update(array('lat'=>$loc['lat'],'lng'=>$loc['lng']),'id = '.$id);
            $row->lat = $loc['lat'];
            $row->lng = $loc['lng'];
        }        
        // Возвращаем результат, упакованный в массив
        return $row->toArray();
    }

    // Метод для добавление новой записи
    public function addStation($name, $timezone, $note = '') {
        

        $loc = Application_Model_Geocoder::getLocation($name);
        
        // Формируем массив вставляемых значений
        $data = array(
            'name' => $this->mb_ucfirst($name),
            'timezone' => $timezone,
            'lat' => $loc['lat'],
            'lng' => $loc['lng'],
            'note' => $note
        );

        $row = $this->fetchRow("name = '" . $name . "'");
        if ($row) {
            $id = $row->id;
        }
        else {
            // Используем метод insert для вставки записи в базу
            try {
                $id = $this->insert($data);
            } catch(Exception $e) {
                echo 'Caught exception: '.  $e->getMessage();
                exit;
            }
        }
        return $id;
    }

    // Метод для обновления записи
    public function updateStation($id, $name, $timezone, $note) {
        
        $address = $name;
        if (!empty($note)) $address .= ", ".$note;
        $loc = Application_Model_Geocoder::getLocation($address);
        
        // Формируем массив значений
        $data = array(
            'name' => $this->mb_ucfirst($name),
            'timezone' => $timezone,
            'lat' => $loc['lat'],
            'lng' => $loc['lng'],
            'note' => $note
        );

        // Используем метод update для обновления записи
        // В скобках указываем условие обновления (привычное для вас where)
        $this->update($data, 'id = ' . (int) $id);
    }

    // Метод для удаления записи
    public function deleteStation($id) {
        // В скобках указываем условие удаления (привычное для вас where)
        $stop_model = new Application_Model_DbTable_Stop();
        $stops = $stop_model->fetchAll('station = ' . $id);
        foreach ($stops as $stop) {
            $train = new Application_Model_DbTable_Train();
            $this_train = $train->fetchRow("id=".$stop->train);
            $train_class = new Application_Model_Train;
            $train_class->getVars($this_train);
            if ($id == $train_class->stops[0]["station_id"] || $id == $train_class->stops[count($train_class->stops)-1]["station_id"]) {
                $train->deleteTrain($train_class->id);
            }
            else {
                $stop_model->deleteStop($stop->id);
            }
        }
        $this->delete('id = ' . (int) $id);
    }

    private function mb_ucfirst($text) {
        mb_internal_encoding("UTF-8");
        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }
    
    public function search($q, $limit = null) {
        $select = $this->select()->where("name LIKE '".$q."%'")->order('name');
        if ($limit) $select = $select->limit($limit);
        $rows = $this->fetchAll($select);
        return $rows;
    }
    
}