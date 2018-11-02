namespace Test;
 class TestString {
      public static function Test(){
      string $a,$b;
     let $a="hello";
     let $b="world";
     echo "$a$b${a}\"nihao\"";
     echo "".$a."".$b.""."!<br>";
  }
  public static function Test1(){
      string $a,$b,$c;
     let $a="hello";
     let $b="world";
     let $c="            <span style=\"color:#f00\">".$a."".$b."<span><br>\r\n";
     echo $c;
  }
}