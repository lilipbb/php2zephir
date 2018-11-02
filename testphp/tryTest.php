<?php
namespace Test{
    class tryTest{
        public static function Test(){
            self::Try1();
            self::Try2();
        }
        public static function Try1(){
            $a="wode";
            try{
                throw new \Exception("error");
            }
            catch (\Exception $e){
                echo($e->getMessage());
            }
            echo('<br>');
        }
        public static function Try2(){
            $a="wode";
            try{
                echo("start  ");
                throw new \Exception("error");
                echo("end  ");
            }
            catch (\Exception $e){
                echo($e->getMessage());
            }
            finally{
                echo "   finally";
            }
            echo('<br>');
        }
    }
}