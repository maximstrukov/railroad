<?php

class Application_Model_Train {

    public $id;
    public $number;
    public $period;
    public $stops;
    
    public function getVars($object) {
        $this->id = $object->id;
        $this->number = $object->number;
        $this->period = $object->period;
        $train_stops = $object->findDependentRowset('Application_Model_DbTable_Stop', 'Train');
        $stops = array();
        foreach ($train_stops as $train_stop) {
            $station = $train_stop->findParentRow('Application_Model_DbTable_Station');
            if (empty($station->lat) || empty($station->lng)) {
                $loc = Application_Model_Geocoder::getLocation($station->name);
                $station_model = new Application_Model_DbTable_Station;
                $station_model->update(array('lat'=>$loc['lat'],'lng'=>$loc['lng']),'id = '.$station->id);
            } else {
                $loc['lat'] = $station->lat;
                $loc['lng'] = $station->lng;
            }
            $timezone = $station->findParentRow('Application_Model_DbTable_Timezone');
            $stops[] = array(
                'station_id'=>$station->id,
                'station_name'=>$station->name,
                'station_timezone'=>$station->timezone,
                'station_timezone_value'=>$timezone->value,
                'station_lat'=>$loc['lat'],
                'station_lng'=>$loc['lng'],
                'arrival'=>$train_stop->arrival,
                'departure'=>$train_stop->departure,
                'order'=>$train_stop->order
            );
        }
        usort($stops, "compare");
        $this->stops = $stops;
    }
    
    public function searchRoute($st_from, $st_to, $limit = null) {
        $stop = new Application_Model_DbTable_Stop();
        $rows = $stop->select()
        ->from('stop',array("stop.train","stop.order","stop.departure"))
        ->join("station", "stop.station=station.id",array())
        ->where("station.name=:st_name AND stop.departure!=''")
        ->query(null, array('st_name'=>$st_from))->fetchAll();
        $data = array();
        $train = new Application_Model_DbTable_Train();
        foreach ($rows as $row) {
            $result = $stop->select()
            ->from('stop',"arrival")
            ->join("station", "stop.station=station.id",array())
            ->where("train=:train AND `name`=:st_name AND `order`>:order")
            ->query(null, array('train'=>$row['train'],'st_name'=>$st_to,'order'=>$row['order']))->fetch();
            if ($result) {
                $columns = "t.number, t.period,";
                $columns .= "(select station from `stop` where train=t.id and arrival='') as station_from_id,";
                $columns .= "(select name from station where id=(select station from `stop` where train=t.id and arrival='')) as station_from_name,";
                $columns .= "(select station from `stop` where train=t.id and departure='') as station_to_id,";
                $columns .= "(select name from station where id=(select station from `stop` where train=t.id and departure='')) as station_to_name";
                $train_info = $train->select()->from("train as t",$columns)->where("t.id=:train")->query(null, array('train'=>$row['train']))->fetch();
                $data[] = array(
                    'id' => $row['train'],
                    'departure' => $row['departure'],
                    'arrival' => $result['arrival'],
                    'info' => $train_info
                );
            }
        }
        return $data;
    }    
    
}

function compare($a, $b) {
    if ($a["order"] == $b["order"]) {
        return 0;
    }
    return ($a["order"] < $b["order"]) ? -1 : 1;
}