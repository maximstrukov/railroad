<?php
class YoutubeController extends Zend_Controller_Action {

    private $db;
    
    public function init() {
        /* Initialize action controller here */
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }

    public function indexAction() {
        //$this->_helper->layout->disableLayout();
        //$this->_helper->layout->setLayout('own');
        $request = $this->getRequest();
        $post = $request->getParams();
        if ($request->isPost() && isset($post["id"])) {
            $query = $this->db->query("SELECT request,category FROM youtube_request WHERE id=".$post["id"])->fetch();
            $params = '&key=AIzaSyCyENzqBhw8BaLljWn5Prs6QMJUGCoCYpE';
            $params .= '&part=snippet';
            $params .= '&maxResults=21';
            $params .= '&order=date';
            $params .= '&type=video';
            $query = urldecode($query["request"]);
            $q = str_replace(" ", "+", $query);
            $url = "https://www.googleapis.com/youtube/v3/search?q=".$q.$params;
            $results = json_decode(file_get_contents($url));
            $videos = array();
            foreach ($results->items as $item) {
                $published = explode("T",$item->snippet->publishedAt);
                $video['published'] = $published[0];
                $video['id'] = $item->id->videoId;
                $video['img'] = $item->snippet->thumbnails->default->url;
                $video['title'] = $item->snippet->title;
                $videos[] = $video;
            }
            echo json_encode(array(
                'id'=>$post["id"],
                'query'=>iconv("windows-1251","UTF-8",ucwords(iconv("UTF-8", "windows-1251",$query))),
                'videos'=>$videos
            ));
            exit;
        }
        $ids_main = $this->db->query("SELECT id FROM youtube_request WHERE status=1")->fetchAll();
        $this->view->ids_main = $ids_main;
        
        
        $categories = $this->db->query("SELECT * FROM youtube_category ORDER BY id")->fetchAll();
        $cat_ids = array();
        foreach ($categories as $category) {
            $cat_ids[$category['id']] = $this->db->query("SELECT id FROM youtube_request WHERE category=".$category['id'])->fetchAll();
        }
        //var_dump($cat_ids);
        $this->view->cat_ids = $cat_ids;
        $this->view->categories = $categories;
    }
    
}