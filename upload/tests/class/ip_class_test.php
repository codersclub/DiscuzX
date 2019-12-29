<?php

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

    function test_checkaccess() {
        $iplist = "2408:84e1:126:5848:987b:bbca:8fb:fd78\r\nfe80\r\n100.64.96.69\r\n100.90";
        assertTrue(ip::checkaccess("100.64.96.69", $iplist));//IPv4, 单个IP
        assertTrue(ip::checkaccess("100.90.96.69", $iplist));//IPv4，按前缀限制
        assertFalse(ip::checkaccess("127.0.0.1", $iplist));//IPv4，未在限制列表
        assertTrue(ip::checkaccess("2408:84e1:126:5848:987b:bbca:8fb:fd78", $iplist));//IPv6, 单个IP
        assertTrue(ip::checkaccess("fe80::a1d8:428b:87a0:3a6a", $iplist));//IPv6，按前缀限制
        assertFalse(ip::checkaccess("::1", $iplist));//IPv6，未在限制列表
    }

}

?>