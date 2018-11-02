namespace Test;
 class TestInlineFun {
      public static function InlineFun(){
      var $fun;
     let $fun=new \Test\Closure__InlineFunfun(null);
     {$fun}();
  }
  public static function InlineFun1(){
      int $a,$b;
      var $fun;
     let $a=111;
     let $b=222;
     let $fun=new \Test\Closure__InlineFun1fun(null);
     echo({$fun}($a,$b)."<br>");
  }
  public static function InlineFun2(){
      int $a,$b;
      var $fun;
     let $a=111;
     let $b=222;
     let $fun=new \Test\Closure__InlineFun2fun(null,$a,$b);
     let $fun->a=$a;
     let $fun->b=$b;
     {$fun}(111,222);
  }
  public static function InlineFun3(){
      var $a,$fun;
      int $b;
     let $a=111;
     let $b=222;
     let $fun=new \Test\Closure__InlineFun3fun(null,$a,$b);
     let $fun->a=$a;
     let $fun->b=$b;
     {$fun}(333,444);
     let $a=$fun->a;
     echo($a."<br>");
  }
}