<?php

class Application_Form_Station extends Zend_Form {
    
    // Метод init() вызовется по умолчанию
    public function init() {
        // Задаём имя форме
        $this->setName('station');

        // Создаём элемент hidden c именем = id
        $id = new Zend_Form_Element_Hidden('id');
        // Указываем, что данные в этом элементе фильтруются как число int
        $id->addFilter('Int')
            ->setDecorators(array(
                'ViewHelper',
                array('Description', array('escape' => false, 'tag' => false)),
                array('HtmlTag', array('tag' => 'dd','id'=>'station_id')),
                array('Label', array('tag' => 'dt')),
                'Errors',
              ))
            ->setDescription('&nbsp;');
            
        // Создаём переменную, которая будет хранить сообщение валидации
        $isEmptyMessage = 'Значение является обязательным и не может быть пустым';

        // Создаём элемент формы – text c именем = name
        $name = new Zend_Form_Element_Text('name');

        /*
        * Далее пишем содержание label, который будет отображаться для данного поля,
        * указываем, является элемент обязательным или нет,
        * пишем список фильтров, которые будут применяться к данному элементу,
        * и наконец, указываем валидатор и сообщение об ошибке, которое будет выведено пользователю
        */
        $action = Zend_Controller_Front::getInstance()->getRequest()->getParam('action');
        $add_more = '<a href="javascript:void(0)" onclick="add_station()" class="add_more">Добавить еще</a>';
        $name->setLabel('Название')
            ->setRequired(true)
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', true,
                array('messages' => array('isEmpty' => $isEmptyMessage))
            );
        
        $timezone = new Zend_Form_Element_Select(
            'timezone',
            array(
               "label" => "Врем. зона",
               "required" => true,
            )
        );
        
        $timezones = new Application_Model_DbTable_Timezone();
        $select = $timezones->select()->order('id');
        $rows = $timezones->fetchAll($select);
        $timezones = array();
        foreach ($rows as $row) $timezones[$row->id] = $row->value." - ".$row->description;
        $timezone->addMultiOptions($timezones);
        $tz = "17";
        $timezone->setValue($tz);
        
        $stations = '';
        
        if (isset($_POST["names"])) {
        
            foreach ($_POST["names"] as $n => $val) {
                $name_val = trim($val);
                if (!empty($name_val)) {
                    $stations .= '<div class="field_separator"></div><input class="names" type="text" value="'.$name_val.'" name="names[]">';
                    $timezone_select = '<select class="timezones" name="timezones[]">';
                    foreach ($timezones as $key => $text) {
                        $timezone_select .= '<option value="'.$key.'"';
                        if ($key == $_POST["timezones"][$n]) $timezone_select .= ' selected';
                        $timezone_select .= '>'.$text.'</option>';
                    }
                    $timezone_select .= '</select>';
                    $stations .= '<div class="field_separator"></div>'.$timezone_select.'<div class="field_separator"></div>';
                }
            }
        }
        
        $timezone->setDecorators(array(
            'ViewHelper',
            array('Description', array('escape' => false, 'tag' => false)),
            array('HtmlTag', array('tag' => 'dd','id'=>'timezone-element')),
            array('Label', array('tag' => 'dt')),
            'Errors',
          ))
        ->setDescription($stations);

        $note = new Zend_Form_Element_Text('note');
        $add_more = '<a href="javascript:void(0)" onclick="add_station()" class="add_more">Добавить еще</a>';
        $note->setLabel('Примечание')
            ->setRequired(false)
            ->addFilter('StripTags')
            ->addFilter('StringTrim');
        
        if ($action=='add') {
            $note->setDecorators(array(
                'ViewHelper',
                array('Description', array('escape' => false, 'tag' => false)),
                array('HtmlTag', array('tag' => 'dd','id'=>'names')),
                array('Label', array('tag' => 'dt')),
                'Errors',
              ))
            ->setDescription($add_more);
        }        
        
        // Создаём элемент формы Submit c именем = submit
        $submit = new Zend_Form_Element_Submit('submit');
        // Создаём атрибут id = submitbutton
        $submit->setAttrib('id', 'submitbutton');

        // Добавляем все созданные элементы к форме.
        $this->addElements(array($id, $name, $timezone, $note, $submit));
    }
}