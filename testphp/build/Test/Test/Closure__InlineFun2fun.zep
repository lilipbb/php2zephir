namespace Test;
 class Closure__InlineFun2fun {
  protected $neithis;
  public $b;
  public $a;
  public $d;
  public $c;
  public function __construct($neithis,$a,$b){
     let $this->neithis=$neithis;
     let $this->a=$a;
     let $this->b=$b;
  }
  public function __invoke($c,$d,$_a_0=null,$_b_0=null){
      var $neithis;
     let $neithis=$this->neithis;
       if($c!=null){
             let $this->c=$c;
       }
       if($d!=null){
             let $this->d=$d;
       }
     echo $this->a+$this->b-$this->c-$this->d,"<br>";
  }
}