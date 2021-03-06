<?php

use Framework\DB\DB;

/**
 * Created by PhpStorm.
 * User: michael
 * Date: 10/19/15
 * Time: 11:20
 */
class DBUpdateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DB
     */
    private $db;

    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->db = DB::connection("read");
    }


    public function testUpdate() {
        $result = $this->db->update("update test set name=?,age=?;",
            array("ssss","22"));

        $this->assertTrue($result>0);
    }
    public function testUpdateMultipleLines() {
        $id = $this->db->insert("insert into test(name,age,adress,is_active,created_at) VALUE (?,?,?,?,now());",
            array("ssss","22","ddsdsd","1"));

        $result = $this->db->update("
        update test
        set name=?,age=? where id=?;",
            array("dddd","2222",$id));

        $this->assertTrue($result>0);
    }

}
