<?php
namespace Test{
    class Test{
        //字符串测试
        public static function StringTest(){
            $a="hello";
            $b="world";
            print("{$a}${b}"."!");
            $c=<<<Str
            <span>{$a}{$b}<span>
Str;
            echo $c;
        }
        //内部函数测试
        public static function InlineFunTest(){
            $fun=function (){ echo("fun1"); };
            echo($fun());
        }
        //内部函数测试
        public static function InlineFunTest1(){
            $a=1;
            $b=2;
            $fun=function()use($a,$b){ echo($a+$b); };
            $fun();
            
        }
    }
}