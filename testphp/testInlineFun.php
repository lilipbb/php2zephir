<?php
namespace Test{
    class TestInlineFun{
        public static function InlineFun(){
            $fun=function (){ echo("fun1<br>"); };
            $fun();
        }
        public static function InlineFun1(){
            $a=111;
            $b=222;
            $fun=function()use($a,$b){return $a+$b; };
            echo($fun().'<br>');
        }
        public static function InlineFun2(){
            $a=111;
            $b=222;
            $fun=function($c,$d)use($a,$b){ echo $a+$b-$c-$d,'<br>';};
            $fun(111,222);
        }
        //测试内部函数使用引用传值
        public static function InlineFun3(){
            $a=111;
            $b=222;
            $fun=function($c,$d)use(&$a,$b){ echo $a+$b-$c-$d,'<br>';$a+=$b+$c+$d;};
            $fun(333,444);
            echo($a.'<br>');
        }
    }
}