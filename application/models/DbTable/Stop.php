<?php

class Application_Model_DbTable_Stop extends Zend_Db_Table_Abstract {

    protected $_name = 'stop';

    protected $_referenceMap    = array(
        'Train' => array(
            'columns'           => 'train',
            'refTableClass'     => 'Application_Model_DbTable_Train',
            'refColumns'        => 'id'
        ),
        'Station' => array(
            'columns'           => 'station',
            'refTableClass'     => 'Application_Model_DbTable_Station',
            'refColumns'        => 'id'
        )
    );
    
    // Метод для получения записи по id
    public function getStop($id) {
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
    public function addStop($station, $train, $arrival, $departure, $order) {
        // Формируем массив вставляемых значений
        $data = array(
            'station' => $station,
            'train' => $train,
            'arrival' => $arrival,
            'departure' => $departure,
            'order' => $order
        );

        // Используем метод insert для вставки записи в базу
        $this->insert($data);
    }

    // Метод для обновления записи
    public function updateStop($id, $station, $train, $arrival, $departure, $order) {
        // Формируем массив значений
        $data = array(
            'station' => $station,
            'train' => $train,
            'arrival' => $arrival,
            'departure' => $departure,
            'order' => $order
        );

        // Используем метод update для обновления записи
        // В скобках указываем условие обновления (привычное для вас where)
        $this->update($data, 'id = ' . (int) $id);
    }

    // Метод для удаления записи
    public function deleteStop($id) {
        // В скобках указываем условие удаления (привычное для вас where)
        $this->delete('id = ' . (int) $id);
    }
    
}