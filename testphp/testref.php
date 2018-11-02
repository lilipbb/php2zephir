<?php
namespace Test{
    class TestRef{
        public static function Add(int &$a){
            $a+=1;
        }
        public static function Sub(int &$a,int &$b){
            $a-=$b;
            $b++;
        }
    }
}