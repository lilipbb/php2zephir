namespace Test;
 class Aa {
      public $b;
  public $a;
  public function __construct($a,$b){
     let $this->a=$a;
     let $this->b=$b;
  }
  public function Hello(){
     echo "ClassA:".($this->a+$this->b)."<br>";
  }
}