namespace Test;
 class TestCondition {
      public static function Test(){
      var $t;
     let $t=new \Test\TestCondition();
     $t->IfTest(true);
     $t->SwitchTest("1");
     $t->ForTest();
     $t->ForeachTest();
  }
  public function IfTest($ok){
      string $mes;
     echo "if:";
       if($ok){
             let $mes="test";
     if($mes=="1"){
               echo 1;
        }
     elseif($mes=="test"){
               echo "ok";
        }
     else{
               echo "nook";
        }
       }
       else{
             echo "no";
       }
     echo "<br>";
  }
  public function SwitchTest(string $s){
     echo "swith:";
       switch($s){
             case "1":echo 1;
             break;
             case "2":echo 2;
             break;
             default:echo 3;
       }
     echo "<br>";
  }
  public function ForTest(){
      var $sum,$a;
      int $i;
     let $sum=0;
         let $i=0;
    loop{
     if(!($i<=10)){
               break;
        }
             let $a=$i;
     if($a%2==0){
      loop{
       if(!($a >= 0)){
                   break;
          }
         let $a=$a-(1);
                 let $sum=$sum+( $a);
         }
        }
       let $i=$i+(1);
       }
     echo "while:".$sum."<br>";
  }
  public function ForeachTest(){
      var $list,$value,$k;
     let $list=["a":1,"b":2,"c":3];
     echo "foreach:";
       for $value in (  $list
){
             echo "value:".$value."";
       }
     echo "<br>";
       for $k,$value in (  $list
){
             echo "key:".$k." value:".$value."";
       }
     echo "<br>";
  }
}