<?php
namespace Test{
    class TestCondition{
        public static function Test(){
            $t=new TestCondition();
            $t->IfTest(true);
            $t->SwitchTest("1");
            $t->ForTest();
            $t->ForeachTest();
        }
        public function IfTest(bool $ok){
            echo 'if:';
            if($ok) {
                $mes="test";
                if($mes=="1")
                    echo 1;
                else if($mes=="test")
                    echo "ok";
                else
                    print("nook");
            }
            else
                print("no");
            echo '<br>';
        }
        public function SwitchTest(string $s){
            echo 'swith:';
            switch ($s){
                case "1":print 1;
                break;
                case "2":print 2;
                    break;
                default:print 3;
            }
            echo '<br>';
        }
        public function ForTest(){
            $sum=0;
            for($i=0;$i<=10;$i++){
                $a=$i;
                if($a%2==0) {
                    while ($a-- >= 0) {
                        $sum += $a;
                    }
                }
            }
            echo 'while:'.$sum.'<br>';
        }
        public function ForeachTest(){
            $list=['a'=>1,'b'=>2,'c'=>3];
            echo("foreach:");
            foreach($list as $value){
                echo("value:$value");
            }
            echo "<br>";
            foreach($list as $k=>$value){
                echo("key:$k value:$value");
            }
            echo "<br>";
        }
    }
}