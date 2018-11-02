namespace Test;
 class Bb extends Aa{
      public function Hello(){
     echo "ClassB:".($this->a-$this->b)."<br>";
  }
  protected function __getmems(){
     return ["b":$this->b,"a":$this->a];
  }
}