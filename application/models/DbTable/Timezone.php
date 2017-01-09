<?php

class Application_Model_DbTable_Timezone extends Zend_Db_Table_Abstract {

    protected $_name = 'timezone';
    
    protected $_dependentTables = array('Station');
    
}