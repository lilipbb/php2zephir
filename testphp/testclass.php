<?php
namespace Test{
    class Aa{
       public $a;
       public $b;
       public function __construct($a,$b)
       {
           $this->a=$a;
           $this->b=$b;
       }

        public function Hello(){
           echo "ClassA:".($this->a+$this->b)."<br>";
       }
    }
    class Bb extends Aa{
        public function Hello(){
            echo "ClassB:".($this->a-$this->b)."<br>";
        }
    }
    class TestClass{
        public static function Test(){
            $a=new Aa(1,2);
            $b=new Bb(1,2);
            $a->Hello();
            $b->Hello();
        }
    }
}