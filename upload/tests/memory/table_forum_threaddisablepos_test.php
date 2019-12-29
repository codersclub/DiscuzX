<?php

class TestThreadDisablePos
{
        private $bydb;
        private $bymem;

        function setUp() {
                $oldvalue = C::memory()->enable;
                C::memory()->enable = false;
                $this->bydb = new table_forum_threaddisablepos();
                C::memory()->enable = true;
                $this->bymem = new table_forum_threaddisablepos();
                C::memory()->enable = $oldvalue;
        }

        function testMemInsert() {
                $this->bymem->truncate();
                $this->bymem->insert(array('tid' => 1234));
                assertTrue($this->bymem->fetch(1234));
                $this->bymem->truncate();
                assertFalse($this->bymem->fetch(1234));
        }

        function testDbInsert() {
                $this->bydb->truncate();
                $this->bydb->insert(array('tid' => 1234));
                assertTrue($this->bydb->fetch(1234));
                $this->bydb->truncate();
                assertFalse($this->bydb->fetch(1234));
        }
}