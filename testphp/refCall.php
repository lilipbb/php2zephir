<?php
namespace Test{
    class RefCall{
        public static function RefTest(){
            $a=1;
            $b=2;
            TestRef::Add($a);
            echo '$a:'.$a."<br>";
            TestRef::Sub($a,$b);
            echo "\$a:{$a}<br>";
            echo "\$b:{$b}<br>";
        }
    }
}