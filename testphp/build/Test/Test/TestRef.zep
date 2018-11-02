namespace Test;
 class TestRef {
      public static function Add(int $a){
     let $a=$a+(1);
  }
  public static function ref__Add(var $__refcallobject){
     let $__refcallobject->a=$__refcallobject->a+(1);
  }
  public static function Sub($a,int $b){
     let $a=$a-($b);
     let $b=$b+(1);
  }
  public static function ref__Sub(var $__refcallobject){
     let $__refcallobject->a=$__refcallobject->a-($__refcallobject->b);
     let $__refcallobject->b=$__refcallobject->b+(1);
  }
}