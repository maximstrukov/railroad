<?php

class Admin_YoutubeController extends Zend_Controller_Action {

    private $db;
    
    public function init() {
        //$this->_helper->layout->setLayout('admin');
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }
    
    public function indexAction() {
        $request = $this->getRequest();
        $params = $request->getParams();
        if ($request->isPost() && isset($params["rows"])) {
            $this->db->query("TRUNCATE TABLE youtube_request");
            $insert = '';
            foreach ($params["rows"] as $row) {
                if (!empty($row[0])) {
                    if (!empty($insert)) $insert .= ",";
                    $insert .= "('','".mysql_escape_string(trim($row[0]))."',".$row[1].",".$row[2].")";
                }
            }
            if (!empty($insert)) $this->db->query("INSERT INTO youtube_request VALUES ".$insert." ON DUPLICATE KEY UPDATE id=id");
        }
        $rows = $this->db->query("SELECT * FROM youtube_request ORDER BY id")->fetchAll();
        $categories = $this->db->query("SELECT * FROM youtube_category ORDER BY id")->fetchAll();
        if ($request->isPost() && isset($params["rows"])) {
            die(json_encode(array("result"=>"ok", "rows"=>$rows, "categories"=>$categories)));
        } else {
            $this->view->rows = $rows;
            $this->view->categories = $categories;
        }
    }
    
    public function addAction() {
        $request = $this->getRequest();
        $params = $request->getParams();
        if ($request->isPost() && isset($params["row"])) {
            $row = trim($params["row"]);
            $category = $params['category'];
            $count = $this->db->query("SELECT COUNT(*) c FROM youtube_request WHERE request='".$row."'")->fetch();
            if ($count['c'] > 0) $id = 0;
            else {
                $this->db->query("INSERT INTO youtube_request VALUES ('','".mysql_escape_string($row)."',1,".$category.")");
                $id = $this->db->lastInsertId();
            }
            $categories = $this->db->query("SELECT * FROM youtube_category ORDER BY id")->fetchAll();
            die(json_encode(array("result"=>"ok","id"=>$id, "categories"=>$categories)));
        }
    }
    
    public function deleteAction() {
        $request = $this->getRequest();
        $params = $request->getParams();
        if ($request->isPost() && isset($params["id"])) {
            $this->db->query("DELETE FROM youtube_request WHERE id=".$params["id"]);
            die(json_encode(array("result"=>"ok", "id"=>$params["id"])));
        }
    }
    
    public function saveAction() {
        $request = $this->getRequest();
        $params = $request->getParams();
        if ($request->isPost() && isset($params["id"]) && (isset($params["request"]) || isset($params["status"]) || isset($params["category"]))) {
            if (isset($params["request"])) $update = "request='".mysql_escape_string(trim($params["request"]))."'";
            elseif (isset($params["status"])) $update = "status=".$params["status"];
            elseif (isset($params["category"])) $update = "category=".$params["category"];
            $this->db->query("UPDATE youtube_request SET ".$update." WHERE id=".$params["id"]);
            die("ok");
        }
    }
    
}