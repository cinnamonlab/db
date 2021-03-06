<?php
use Framework\DB\DB;

/**
 * Created by PhpStorm.
 * User: michael
 * Date: 10/19/15
 * Time: 11:17
 */
class DBInsertTest extends PHPUnit_Framework_TestCase
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


    public function testInsert() {
        $result = $this->db->insert("insert into test(name,age,adress,is_active,created_at) VALUE (?,?,?,?,now());",
            array("ssss","22","ddsdsd","1"));

        $this->assertTrue($result>0);
    }
    public function testMultipleInserts() {
        $result = $this->db->insert("insert into test(name,age,adress,is_active,created_at) VALUES (?,?,?,?,now()),(?,?,?,?,now());",
            array("dddddd","24","ddsdsd","1","ssssss","35","sdsdsd","1"));

        $this->assertTrue($result>0);
    }
}
