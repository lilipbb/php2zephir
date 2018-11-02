namespace Test;
 class RefCall {
      public static function RefTest(){
      var $a,$b,$reft__obj;
     let $a=1;
     let $b=2;
     let $reft__obj=(object)null;let $reft__obj->a=$a;TestRef::ref__Add($reft__obj);
let $a=$reft__obj->a;
     echo "$a:".$a."<br>";
     let $reft__obj=(object)null;let $reft__obj->a=$a;let $reft__obj->b=$b;TestRef::ref__Sub($reft__obj);
let $a=$reft__obj->a;let $b=$reft__obj->b;
     echo "$a:".$a."<br>";
     echo "$b:".$b."<br>";
  }
}