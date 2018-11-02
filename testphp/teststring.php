<?php
namespace Test{
    class TestString{
        public static function Test(){
            $a="hello";
            $b="world";
            print('$a$b${a}"nihao"');
            print("$a${b}"."!<br>");
        }
        public static function Test1(){
            $a="hello";
            $b="world";
            $c=<<<Str
            <span style="color:#f00">{$a}{$b}<span><br>
Str;
            echo $c;
        }
    }
}