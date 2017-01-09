<?php

class Application_Form_Train extends Zend_Form {
    
    public $stops_errors = false;
    public $number_error = false;
    public $new_name = 'Новое название';
    
    private $timezone_rows;
    
    // Метод init() вызовется по умолчанию
    public function init() {
        // Задаём имя форме
        $this->setName('train');
        
        $action = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $train_id = $request->getParam("id");
        
        // Создаём элемент hidden c именем = id
        $id = new Zend_Form_Element_Hidden('id');
        // Указываем, что данные в этом элементе фильтруются как число int
        $id->addFilter('Int')
            ->setDecorators(array(
                'ViewHelper',
                array('Description', array('escape' => false, 'tag' => false)),
                array('HtmlTag', array('tag' => 'dd','id'=>'train_id')),
                array('Label', array('tag' => 'dt')),
                'Errors',
              ))
            ->setDescription('&nbsp;');

        if ($action=='edit' && $train_id) {
            $train = new Application_Model_DbTable_Train();
            $data = $train->getTrain($train_id);
            $hiddenControl = $this->createElement('hidden', 'old_number');
            $hiddenControl->setValue($data["number"]);
            $this->addElements(array($hiddenControl));
        }
        
        // Создаём переменную, которая будет хранить сообщение валидации
        $isEmptyMessage = 'Значение является обязательным и не может быть пустым';
        $timeFormatMessage = 'Значение не соответствует формату времени (ЧЧ:ММ)';
        $uniqueRecord = 'В базе данных уже есть поезд с номером %value%';
        
        $number = new Zend_Form_Element_Text('number');

        $number->setLabel('Номер')
            ->setRequired(true)
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', true, array('messages' => array('isEmpty' => $isEmptyMessage)));
        if ($action=='add') {
            $number->addValidator(new Zend_Validate_Db_NoRecordExists('train', 'number'));
            $number->getValidator('Db_NoRecordExists')->setMessage($uniqueRecord);
        }
        elseif ($action=='edit' && isset($_POST["number"])) {
            $new_number = trim($_POST["number"]);
            $old_number = trim($_POST["old_number"]);
            $train = new Application_Model_DbTable_Train();
            $row = $train->fetchRow("`number`='".$new_number."' AND `number` != '".$old_number."'");
            //if ($row) die("yest");
            //else die("net");
            if ($row) {
                $number->setDecorators(array(
                    'ViewHelper',
                    array('Description', array('escape' => false, 'tag' => false)),
                    array('HtmlTag', array('tag' => 'dd','id'=>'number_block')),
                    array('Label', array('tag' => 'dt')),
                ))
                ->setDescription('<ul class="errors"><li>'.str_replace('%value%',$new_number,$uniqueRecord).'</li></ul>');
                $this->number_error = true;
            }
        }
        $period = new Zend_Form_Element_Select(
            'period',
            array(
               "label" => "Период",
               "required" => true,
            )
        );
        $period->addMultiOptions(
            array(
                "ежедневно" => "ежедневно",
                "по четным" => "по четным",
                "по нечетным" => "по нечетным"
            )
        );
        
        $this->addElements(array($id, $number, $period));
        
        $formData = $request->getPost();

        $edit_stops = $edit_timezones = $edit_arrivals = $edit_departures = array();
        if ($action=='edit' && $train_id) {
            $train = new Application_Model_DbTable_Train();
            $this_train = $train->fetchRow("id=".$train_id);
            $train_class = new Application_Model_Train;
            $train_class->getVars($this_train);
            $timezone_from = $train_class->stops[0]["station_timezone"];
            $timezone_to = $train_class->stops[count($train_class->stops)-1]["station_timezone"];
            if (count($train_class->stops) > 2) {
                for ($s = 1; $s < (count($train_class->stops)-1); $s++) {
                    $edit_stops[] = array(
                        "station"=>$train_class->stops[$s]["station_name"],
                        "timezone"=>$train_class->stops[$s]["station_timezone"]
                    );
                    $edit_timezones[] = $train_class->stops[$s]["station_timezone"];
                    $edit_arrivals[] = $train_class->stops[$s]["arrival"];
                    $edit_departures[] = $train_class->stops[$s]["departure"];
                }
            }
        }        
        
        $stations = new Application_Model_DbTable_Station();
        $select = $stations->select();
        $select->order('name');
        $rows = $stations->fetchAll($select);

        $station_from = new Zend_Form_Element_Text('station_from');
        $station_from->setLabel('Станция отправления')
            ->setRequired(true)
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', true, array('messages' => array('isEmpty' => $isEmptyMessage)));

        $timezones = new Application_Model_DbTable_Timezone();
        $select = $timezones->select()->order('id');
        $this->timezone_rows = $timezones->fetchAll($select);
        if (isset($formData["timezone_from"])) $timezone_from = $formData["timezone_from"];
        if (isset($formData["timezone_to"])) $timezone_to = $formData["timezone_to"];
        if (!isset($timezone_from)) $timezone_from = 17;
        if (!isset($timezone_to)) $timezone_to = 17;

        $station_from->setDecorators(array(
            'ViewHelper',
            array('Description', array('escape' => false, 'tag' => false)),
            array('HtmlTag', array('tag' => 'dd','id'=>'station_from_block')),
            array('Label', array('tag' => 'dt')),
            'Errors',
          ))
        ->setDescription($this->get_timezone("timezone_from",$timezone_from));
        $station_from->class="station_input";

        $this->addElements(array($station_from));
        
        $time_from = new Zend_Form_Element_Text('time_from');
        $view = new Zend_View;
        $add_stop = '<div class="add_stop"><a href="javascript:void(0)" onclick="add_stop(\''.$view->escape($this->get_timezone("timezone[]",17)).'\')">Добавить остановку</a></div>';
        $add_stop .= '<table id="stop_rows"><tbody>';
        
        if (isset($_POST["stops"]) || !empty($edit_stops)) {
            $stops = "";
            if (!empty($edit_stops)) {
                $way_stops = $edit_stops;
                $timezones = $edit_timezones;
                $arrivals = $edit_arrivals;
                $departures = $edit_departures;
            }
            else {
                $way_stops = $_POST["stops"];
                $timezones = $_POST["timezone"];
                $arrivals = $_POST["arrival"];
                $departures = $_POST["departure"];
                
            }
            foreach ($way_stops as $n => $stop) {
                if (empty($edit_stops)) $stops_text = trim($_POST["stops"][$n]);
                else $stops_text = $stop["station"];
                $timezone = $timezones[$n];
                $arrival = trim($arrivals[$n]);
                $departure = trim($departures[$n]);
                
                $stops .= '<tr><td><a href="javascript:void(0)" onclick="delete_stop(this)" class="stop_delete"></a></td><td class="dragHandle"> </td>';
                $stops .= '<td><input class="station_input" type="text" value="'.$stops_text.'" name="stops[]"/>';
                $stops .= '<span class="add_time_label">Временная зона</span>'.$this->get_timezone("timezone[]", $timezone);
                $stops .= '<span class="add_time_label">Прибытие</span><input type="text" name="arrival[]" value="'.$arrival.'"/>';
                $stops .= '<span class="add_time_label">Отправление</span><input type="text" name="departure[]" value="'.$departure.'"/>';
                $stops .= '<div class="clear stops_delimiter"></div></td></tr>';
                
                if (!empty($stop["station"]) || !empty($stops_text)) {
                    if (empty($arrival)) {
                        $stops .= '<ul class="errors"><li>Время прибытия является обязательным и не может быть пустым</li></ul>';
                        $this->stops_errors = true;
                    }
                    elseif (!preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/si', $arrival)) {
                        $stops .= '<ul class="errors"><li>Время прибытия не соответствует формату времени (чч:мм)</li></ul>';
                        $this->stops_errors = true;
                    }
                    if (empty($departure)) {
                        $stops .= '<ul class="errors"><li>Время отправления является обязательным и не может быть пустым</li></ul>';
                        $this->stops_errors = true;
                    }
                    elseif (!preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/si', $departure)) {
                        $stops .= '<ul class="errors"><li>Время отправления не соответствует формату времени (чч:мм)</li></ul>';
                        $this->stops_errors = true;
                    }
                }
            }
            $add_stop .= $stops;
        }
        $add_stop .= '</tbody></table>';
        
        $time_from->setLabel('Время отправления')
            ->setRequired(true)
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', true, array('messages' => array('isEmpty' => $isEmptyMessage)))
            ->addValidator('regex', false,
                array(
                    'pattern'=>'/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/si',
                    'messages'=>array(
                       'regexNotMatch' => $timeFormatMessage
                    )
                )
            )
            ->setDecorators(array(
                'ViewHelper',
                array('Description', array('escape' => false, 'tag' => false)),
                array('HtmlTag', array('tag' => 'dd','id'=>'time_from_block')),
                array('Label', array('tag' => 'dt')),
                array('Errors'),
                array(array('ErrorsDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'all_stops')),
            ))
            ->setDescription($add_stop);
        
        $this->addElements(array($time_from));
        
        $station_to = new Zend_Form_Element_Text('station_to');
        $station_to->setLabel('Станция прибытия')
            ->setRequired(true)
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', true, array('messages' => array('isEmpty' => $isEmptyMessage)));            

        $station_to->setDecorators(array(
            'ViewHelper',
            array('Description', array('escape' => false, 'tag' => false)),
            array('HtmlTag', array('tag' => 'dd','id'=>'station_to_block')),
            array('Label', array('tag' => 'dt')),
            'Errors',
          ))
        ->setDescription($this->get_timezone("timezone_to", $timezone_to));
        $station_to->class="station_input";
        $this->addElements(array($station_to));
        
        $time_to = new Zend_Form_Element_Text('time_to');
        $time_to->setLabel('Время прибытия')
            ->setRequired(true)
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->addValidator('NotEmpty', true, array('messages' => array('isEmpty' => $isEmptyMessage)))
            ->addValidator('regex', false,
                array(
                    'pattern'=>'/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/si',
                    'messages'=>array(
                       'regexNotMatch' => $timeFormatMessage
                    )
                )
            );
        $this->addElements(array($time_to));
        
        // Создаём элемент формы Submit c именем = submit
        $submit = new Zend_Form_Element_Submit('submit');
        // Создаём атрибут id = submitbutton
        $submit->setAttrib('id', 'submitbutton');

        // Добавляем все созданные элементы к форме.
        $this->addElements(array($submit));
    }
    
    private function get_timezone($name, $value) {
        $text = '<select name="'.$name.'" class="timezone_short">';
        foreach ($this->timezone_rows as $row) {
            $text .= '<option value="'.$row->id.'"';
            if ($row->id == $value) $text .= ' selected';
            $text .= '>'.$row->value.'</option>';
        }
        $text .= "</select>";
        return $text;
    }
    
}