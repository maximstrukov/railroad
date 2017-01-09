<?php

class Application_Model_DbTable_Train extends Zend_Db_Table_Abstract {

    protected $_name = 'train';

    protected $_dependentTables = array('Stop');
    
    // Метод для получения записи по id
    public function getTrain($id) {
        // Получаем id как параметр
        $id = (int) $id;

        // Используем метод fetchRow для получения записи из базы.
        // В скобках указываем условие выборки (привычное для вас where)
        $row = $this->fetchRow('id = ' . $id);

        // Если результат пустой, выкидываем исключение
        if (!$row) {
            throw new Exception("Нет записи с id - $id");
        }
        // Возвращаем результат, упакованный в массив
        return $row->toArray();
    }

    // Метод для добавление новой записи
    public function addTrain($number, $period) {
        // Формируем массив вставляемых значений
        $data = array(
            'number' => $number,
            'period' => $period
        );

        // Используем метод insert для вставки записи в базу
        try {
            $id = $this->insert($data);
        } catch(Exception $e) {
            echo 'Caught exception: '.  $e->getMessage();
            exit;
        }
        return $id;
    }

    // Метод для обновления записи
    public function updateTrain($id, $number, $period) {
        // Формируем массив значений
        $data = array(
            'number' => $number,
            'period' => $period
        );
        // Используем метод update для обновления записи
        // В скобках указываем условие обновления (привычное для вас where)
        $this->update($data, 'id = ' . (int) $id);
    }

    // Метод для удаления записи
    public function deleteTrain($id) {
        // В скобках указываем условие удаления (привычное для вас where)
        $this->delete('id = ' . (int) $id);
        $this->deleteTrainStops($id);
    }
    public function deleteTrainStops($id) {
        $stop = new Application_Model_DbTable_Stop();
        $stop->delete('train = ' . (int) $id);
    }
    
    public function search($q, $limit = null) {
        $qs = array_filter(explode(' ',$q));
        $columns = "t.id, t.number, t.period,";
        $columns .= "(select station from `stop` where train=t.id and arrival='') as station_from_id,";
        $columns .= "(select name from station where id=(select station from `stop` where train=t.id and arrival='')) as station_from_name,";
        $columns .= "(select departure from `stop` where train=t.id and arrival='') as departure,";
        $columns .= "(select station from `stop` where train=t.id and departure='') as station_to_id,";
        $columns .= "(select name from station where id=(select station from `stop` where train=t.id and departure='')) as station_to_name,";
        $columns .= "(select arrival from `stop` where train=t.id and departure='') as arrival";
        $where = '';
        $having = '';
        foreach ($qs as $v) {
            if (is_numeric($v)) $where = "t.number like '".$v."%'";
            else {
                if (!empty($having)) $having .= ' and ';
                $having .= "(station_from_name like '".$v."%' or station_to_name like '".$v."%')";
            }
        }
        $select = $this->select()->from("train as t",$columns);
        
        if (!empty($where)) $select = $select->where($where);
        if (!empty($having)) $select = $select->having($having);
        $select = $select->order(array("station_from_name", "station_to_name"));
        if ($limit) $select = $select->limit($limit);
        
        $rows = $this->fetchAll($select);
        return $rows;
    }

}