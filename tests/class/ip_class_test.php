<?php

define('IN_DISCUZ', true);
require '../upload/source/class/class_ip.php';

class TestIp
{
    function test_to_display() {
        assertEqual("[::1]", ip::to_display("::1"));
        assertNotEqual("[192.168.1.1]", ip::to_display("192.168.1.1"));
    }

    function test_to_ip() {
        assertEqual("::1", ip::to_ip("[::1]"));
        assertEqual("http://::1", ip::to_ip("http://[::1]"));
        assertEqual("http://::1:8080", ip::to_ip("http://[::1]:8080"));
        assertNotEqual("http://::1:8080", ip::to_ip("[http://::1]:8080"));
    }

    function test_check_ip6() {
        assertTrue(ip::check_ip("::1", "::1"));
        assertTrue(ip::check_ip("::1", "::1/64"));
        assertTrue(ip::check_ip("::", "::1/127"));
        assertTrue(ip::check_ip("::2", "::1/126"));
        assertTrue(ip::check_ip("2001:4860:4860::4444", "2001:4860:4860::8888/64"));
        assertTrue(ip::check_ip("64:ff9b::10.10.10.10", "64:ff9b::10.10.10.10/64"));
        assertFalse(ip::check_ip("64:ff9c::10.10.10.10", "64:ff9b::10.10.10.10/64"));
        assertFalse(ip::check_ip("64:ff9c::10.10.10.10", "64:ff9b::/64"));
        assertFalse(ip::check_ip("64:ff9b::10.10.10", "64:ff9b::10.10.10.10/64"));
    }

    function test_check_ip4() {
        assertTrue(ip::check_ip("127.0.0.1", "127.0.0.1"));
        assertTrue(ip::check_ip("127.0.0.1", "127.0.0.1/24"));
        assertFalse(ip::check_ip("127.0.0.2", "127.0.0.1/31"));
        assertTrue(ip::check_ip("127.0.0.2", "127.0.0.1/30"));
    }
}

?>