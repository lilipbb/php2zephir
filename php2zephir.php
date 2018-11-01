<?php
namespace Php2Zephir;
$author='lilipbb';//插件作者名称
$version='1.0.0';//插件版本
const PHP_TYPE_CLASS='class';
const PHP_TYPE_FUNCTION='function';
const PHP_TYPE_MEMBER='member';
const PHP_TYPE_INLINE='inline';
const PHP_TYPE_INTERFACE='interface';
const PHP_TYPE_CONDICTION='cond';
const PHP_MODI_PUBLIC='public';
const PHP_MODI_PROTECTED='protected';
const PHP_MODI_PRIVATE='private';
const PHP_MODI_STATIC='static';
const NEWLINE="\r\n";
define('REFTEMPOBJ','$__refcallobject');
define('EMPTYNODE','baseemptynode');
define('PRINT_OUT',false);
$keywords=['string','int','char','float','long','default','namespace'];
$super=['$GLOBALS','$_SERVER','$_REQUEST','$_POST','$_GET','$_FILES','$_ENV','$_COOKIE','$_SESSION'];
function ChangeDir($src,$dst,$recursion=0){
    $root=new PhpRoot();
    $root->LoadDir($src,$recursion);
    $root->Save($dst);
}
class PhpDocHelp{
    private static $consts;
    public static function replaceConst($text){
        if(self::$consts==null)
        self::$consts=get_defined_constants();
        $text=PhpDocHelp::replaceWithoutString($text,function($mes){
//            $mes=preg_replace_callback('@([\w\d_]+)\s*(\[\d+\])?@',function($match){
//                if(key_exists($match[1],self::$consts)){
//                    $dstconst=self::$consts[$match[1]];
//                    if(gettype($dstconst)=='string'){
//                        if(count($match)>2)
//                            return '"'.$dstconst[substr($match[2],1,-1)].'"';
//                        return  '"'.$dstconst.'"';
//                    }
//                    else {
//                        if(gettype(self::$consts[$match[1]])=='array'){
//                            $mes=json_encode(self::$consts[$match[1]]);
//                            $mes=str_replace('{','[',$mes);
//                            $mes=str_replace('}',']',$mes);
//                            return $mes.$match[2];
//                        }
//                            return self::$consts[$match[1]];
//                    }
//                }
//                return $match[0];
//            },$mes);
            $mes=preg_replace_callback('@([\w\d_]+)\s*(\[.+?\])@',function($match){//常量[]进行转换
                if(key_exists($match[1],self::$consts)) {
                    return "constant(\"$match[1]\")".$match[2];
                }
                return $match[0];
            },$mes);
            return $mes;
         });
        $text=preg_replace_callback('@\$[A-Z]+@',function($match){//对$GLOBALS进行转换
            if($match[0]=='$GLOBALS')return '$_GET';
            return $match[0];
        },$text);
         return $text;
    }
    public static function rmSpaceLine($str){
        $nstr='';
        while(true) {
            $nstr = preg_replace('@(\r\n|\n\r|\n)[\s]*\1@', "\r\n", $str);
            if($nstr==$str)break;
            $str=$nstr;
        }
        return $nstr;
    }
    private static $isphp;
    public static function preStr($str){
        $NEWLINE="\r\n";
        if(ord($str[0])==239&&ord($str[1])==187&&ord($str[2])==191){//去掉utf8文件头
            $str=substr($str,3);
        }
        $str=preg_replace('@/\*[^\'"]*?\*/@s','',$str);//替换/**/的注释内容
        $arr=preg_split('/\r\n|\n/',$str);
        $narr=[];
        self::$isphp=false;
        foreach ($arr as $item) {
            $item=preg_replace('@([\s=;])die\s?;@','$1die();',$item);
            if(!self::$isphp){
                $item=self::replaceWithoutString($item,function($m){
                    if(preg_match('@<[?](php)?@',$m,$match)){
                        self::$isphp=true;
                        $m=str_replace($match[0],'',$m);
                    }
                    return $m;
                });
            }
            if(self::$isphp){
                $item=self::replaceWithoutString($item,function($m){
                    if(preg_match('@(.*)[?]>@',$m,$match)){
                        self::$isphp=false;
                        $m=str_replace($match[0],$match[1],$m);
                    }
                    return $m;
                });
            }
            if(!self::$isphp){continue;}
            $narr[]=$item;
        }
        $nnarr=[];
        foreach ($narr as $item) {
            $item=PhpDocHelp::replaceWithoutString($item,function($m1){
                if(preg_match('@([^/]*)//@',$m1,$match)){
                    return (object)$match[1];
                }

                $m1=preg_replace_callback('@(.)(require_once|include_once|include)(.)@',function($match){
                     if($match[1]==':'||$match[1]=='>')return $match[0];
                     if(preg_match('@\w\d_@',$match[3]))return $match[0];
                     return $match[1].'require'.$match[3];
                },$m1);
                return $m1;
            },function($str){
                 $first=$str[0];
                $str=substr($str,1,-1);
                 if($first=='\''){
                     if(strlen($str)==1&&$str=='"')$str='\\"';
                     $str=preg_replace_callback('@[\\\]{2}|(.?)"@',function($match){
                         if(count($match)<2)return $match[0];
                         if($match[1]=='\\')return $match[0];
                         return $match[1].'\\'.'"';
                     },$str);
                 }else{
                     $str=preg_replace_callback('@[\\\]\$|\$\{(.*?)\}|\{\$(.*?)\}|\$([\w\d_]+\[\d+\])|\$([\w\d_]+)@',function($match){
                         if($match[0]==='\\$')return '$';
                          $mes=trim(end($match));
                          return '".$'.$mes.'."';
                     },$str);
                 }
                 return '"'.$str.'"';
            });
            $nnarr[]=$item;
        }
        return $nnarr;
    }
    private static function getRangeTag($tag){
        for ($i = 0; $i < count(self::$rangetag); $i++) {
            if ($tag == self::$rangetag[$i]) {
                return self::$rangetag[$i + ($i % 2 == 0 ? 1 : -1)];
            }
        }
    }
    public static function splitArgs($args){
        $arr=[];
        $last='';
        $len=strlen($args);
        $index=0;
        $mes='';
        while($index<$len){
            $chr=$args[$index++];
            if($chr=='\\'){
                $mes.='\\'.$args[$index++];
            }
            else if(in_array($chr,self::$rangetag)){
                $l=substr($last,-1);
                if($l==self::getRangeTag($chr))$last=substr($last,0,-1);
                else if($l!='\''&&$l!='"'){
                    $last.=$chr;
                }
                $mes.=$chr;
            }
            else if($chr=='\''||$chr=='"'){
                $l=substr($last,-1);
                if($l==$chr)$last=substr($last,0,-1);
                else if($l!='\''&&$l!='"')$last.=$chr;
                $mes.=$chr;
            }
            else if($chr==','&&$last==''){
                $arr[]=$mes;
                $mes='';
            }
            else $mes.=$chr;
        }
        if($mes!='')$arr[]=$mes;
        return $arr;
    }
    private static $rangetag=['(',')','[',']','{','}'];
    public static function splitIndex($str,...$end)
    {
        $stack = [];
        $i = 0;
        for (; $i < strlen($str); $i++) {
            $c = $str[$i];
            if ($c == '\\') {
                $i++;
                continue;
            } else if ($c == '\'' || $c == '"') {
                if (end($stack) == $c) array_pop($stack);
                else if(end($stack)!= '\''&& end($stack)!= '"') array_push($stack, $c);
            } else if (in_array($c, self::$rangetag)) {
                if (end($stack) == self::getRangeTag($c)) {
                    array_pop($stack);
                    if (count($stack)==0&&(count($end)==0||$end[0]==''||in_array($c,$end))) {
                        while(++$i<strlen($str)){
                            if($str[$i]==' ')
                                continue;
                            else if($str[$i]==';') {
                                $i++;
                                break;
                            }
                            else{
                                break;
                            }
                        }
                        break;
                    }
                }
                else if(end($stack)!='\''&&end($stack)!='"')
                    array_push($stack, $c);
            }
            if ($c == ';' && count($stack) == 0) {
                $i++;
                break;
            }
        }
        return $i;
    }
    public static function replaceWithoutString($str,$fun,callable $otherfun=null){
        $arr=preg_split('@\r\n|\n\r|\n@',$str);
        $narr=[];
        $index=-1;
        foreach ($arr as $item) {
            if(preg_match('@^[ ]*$@',$item)){continue;}
            $index++;
            $narr[$index]='';
            $stack='';
            $nstr='';
            for($i=0;$i<strlen($item);$i++){
                $char=$item[$i];
                if($stack!=''&&$char=='\\'){
                    $nstr.=$char;
                    $i++;
                    if($i<strlen($item))
                    $nstr.=$item[$i];
                    continue;
                }
                if($char=='"'||$char=='\''){
                    if($stack==''){
                        if($nstr!='') {
                            $obj=$fun($nstr);
                            if(gettype($obj)=='object'){
                                if(isset($obj->scalar)) {
                                    $narr[$index].=$obj->scalar;
                                }
                                $nstr='';
                                break;
                            }
                            else
                                $narr[$index] .= $obj;
                        }
                        $nstr=$char;
                        $stack=$char;
                    }
                    else if($stack==$char){
                        $nstr.=$char;
                        if($otherfun!=null){
                            $nstr=@$otherfun($nstr,end($narr),(strlen($item)>$i-1)?$item[$i+1]:'');
                        }
                        $narr[$index].=$nstr;
                        $stack='';
                        $nstr='';
                    }
                    else $nstr.=$char;
                }else{
                    $nstr.=$char;
                }
            }
            if($nstr!='') {
                $obj=$fun($nstr);
                if(gettype($obj)=='object'){
                    if(isset($obj->scalar)) {
                        $narr[$index].=$obj->scalar;
                    }
                }
                else
                    $narr[$index] .= $obj;
            }
        }
        return implode("\r\n",$narr);
    }
    /**
     * 把<<<声明的string转为普通string
     */
    public static function changeETOtoString($str){
        $str=preg_replace_callback('@<<<([\'|"]?)([\w\d_]*)\1(\r\n|\n\r|\n)(.*)(\r\n|\n\r|\n)\2;@s',function($match){
            $char=$match[1]==''?'"':$match[1];
            $arr=preg_split('@\r\n|\n\r|\n@',$match[4]);
            $narr=[];
            foreach ($arr as $item) {
                $str='';
                for ($i=0;$i<strlen($item);$i++){
                    if($item[$i]==$char&&$i>0&&$item[$i-1]!='\\'){
                        $str.='\\'.$item[$i];
                    }else{
                        $str.=$item[$i];
                    }
                }
                if(count($narr)>0)
                    $narr[]='.'.$char.$str.'\r\n'.$char;
                else $narr[]=$char.$str.'\r\n'.$char;
            }
            return implode('',$narr).';';
        },$str);
        return $str;
    }
    public static function getid(){
        static $id=100000;
        return $id++;
    }
    public static function utf8encoding($mes)//处理中文乱码问题
    {
        $mes=preg_replace_callback('/([\x{80}-\x{ff}]{2,})/',function($match){
            return $match[1].'　';
      },$mes);
        return $mes;
    }
    const StrongTypes=[
      'int'=>['(int)','(integer)'],
      'boolean'=>['(bool)','(boolean)'],
        'float'=>['(float)','(double)','(real)'],
        'string'=>['(string)'],
        //'array'=>['(array)']
    ];
    private static $funinfos;
    public static function getVarType($mes,$const=false){//默认是把一整条语句丢进来

         if(self::$funinfos==null){//读取方法参数类型
             self::$funinfos = [];
             if(file_exists("fun.txt")) {
                 $funs = explode(';', file_get_contents("fun.txt"));
                 foreach ($funs as $fun) {
                     $arr = explode("@", $fun);
                     if (count($arr) < 2) continue;
                     if (in_array($arr[1], ['array', ''])) continue;
                     if ($arr[1] == 'bool') $arr[1] = "boolean";
                     self::$funinfos[$arr[0]] = $arr[1];
                 }
             }
         }
         $mes=trim($mes);
         if($mes==''||$mes=='var')return 'var';
         if(preg_match('@\.=@',$mes))return 'string';
         if($const||preg_match('@\$[\w\d_]+\s*=(.+);@s',$mes,$match)){
             if(!$const)
             $mes=trim($match[1]);
             if(preg_match('@^([\w\d_:]+)\s*\(@',$mes,$match)){//查询是否为方法
                 if(key_exists($match[1],self::$funinfos)){
                     return self::$funinfos[$match[1]].'_';
                 }
             }
             foreach (self::StrongTypes as $name=>$strongType) {
                 foreach ($strongType as $item) {
                     if(strpos($mes,$item)===0)
                         return $name;
                 }
             }
             if($mes=='true'||$mes=='false'||preg_match('@^\s*[^=.!]+\s*(==|!=|===|!==)\s*[^=.!]+$@',$mes))return 'boolean';
             if(preg_match('@^new ([\w\d\\\_]+)@',$mes,$m)){//是new类型
                 return $m[1];
             }
             if(preg_match('@^\s*\[.*\]\s*|^\s*array\s*\(@s',$mes)){//匹配array
                 return 'array';
             }
             if(preg_match('@^\s*([\d\.]+)\s*$@s',$mes,$m)){//匹配数字
                  if(strpos($m[1],'.'))return 'float';
                  if(strlen($m[1])>9)
                      return 'long';
                  return 'int';
             }
             if(preg_match('@^["\']|["\']$@',$mes)){
                 return 'string';
             }
             $mes=preg_replace_callback('@\(.*\)|\[.*\]|\{.*\}|\".*\"@',function($match){
                 return '';
             },$mes);
             if(preg_match('@\.@',$mes))return 'string';
         }
         return 'var';
    }
    public static function checkType($type1,$type2)//对类型返回进行检查
    {
        if($type1=='ref'||$type2=='ref')return 'ref';
        if($type1=='var'||$type2=='var')return 'var';
        if($type1==$type2)return $type1;
        if(substr($type1,-1)=='_'&&$type1==$type2.'_')return $type1;
        if(substr($type2,-1)=='_'&&$type1.'_'==$type2)return $type2;
        $canchange=[
            'int'=>['int','boolean'],
            'long'=>['int','long','boolean','float'],
        ];
        foreach ($canchange as $t=>$arr) {
            if(in_array($type1,$arr)&&in_array($type2,$arr))
                return $t;
        }
        return 'var';
    }
}
class PhpRoot{
    public static $instance;
    public $name;
    protected $text;
    protected $childs;
    protected $parent;
    protected $level;
    protected $prechilds;
    protected $endchilds;
    private static $namespace;
    public function LoadDir($path,$recursion=0){
        if(!file_exists($path)){echo($path.'该目录不存在！'.NEWLINE);return;}
        $files=Util::getFiles($path,$recursion,function($name){return substr($name,-4)=='.php';});
        foreach ($files as $file) {
             $this->LoadStr(file_get_contents($file));
        }
    }
    public function LoadStr($mes){
        if(self::$instance==null)self::$instance=$this;
        if($this->name==null)
        $this->initialze('root',null,'');
        $mes=PhpDocHelp::changeETOtoString($mes);
        $str=trim(implode(NEWLINE,PhpDocHelp::preStr($mes)));
        $index=PhpDocHelp::splitIndex($str);
        if($str[$index-1]=='}'){
            $str=substr($str,0,-1);
            $str=preg_replace('/{/',";\r\n",$str,1);
        }
        self::$namespace='';
        $str=preg_replace_callback('@^.*namespace([\\\\d\w_ ]*?);@',function($match){
            $name=explode('\\',trim($match[1]));
            $nname='';
            foreach ($name as $item) {
                if($item!='')
                    $nname.=strtoupper($item[0]).substr($item,1).'\\';
            }
            self::$namespace=trim(substr($nname,0,-1));
        },$str);
        if(self::$namespace==''){
            echo('发现没有命名空间的文件,是否继续？Y or N'.NEWLINE);
            $r=fgets(STDIN);
            if(strtolower($r[0])=='y')
            return;
            die;
        }
        if($this->childs!=null){
            foreach ($this->childs as $child) {
                if($child->name==self::$namespace){
                    $child->load($str);
                    return;
                }
            }
        }
        $this->childs[]=new PhpNode_NameSpace(self::$namespace,$this,'');
        $this->childs[count($this->childs)-1]->load($str);
    }
    public function getAllModelsName(){
        if($this->childs==null||count($this->childs)<1){
            return [];
        }
        $arr=[];
        foreach ($this->childs as $namespace) {
            $arr[]=strtolower(explode('\\',$namespace->name)[0]);

        }
        return array_unique($arr);
    }
    public function Save($path){
        $this->start();
        if($this->childs==null){echo('未载入文件');return;}
        $path=str_replace('\\','/',$path);
        if(substr($path,-1)!='/')$path.='/';
        $delelepath=[];
        if($this->childs==null||count($this->childs)<1){
             echo($this->name."不需要导出!" . NEWLINE);
        }
        foreach ($this->childs as $namespace) {
            $extname=explode('\\',$namespace->name)[0];
            $namespacepath=$path.$extname.'/';
            if(!in_array($namespacepath,$delelepath)) {
                $delelepath[] = $namespacepath;
                if (file_exists($namespacepath)) {
                    foreach(Util::getFiles($namespacepath,-1) as $filename){
                        unlink($filename);
                    }
                }
                //exec('cd /d "' . $path . '"&%PHP_SDK%\bin\phpsdk_setvars' . "&zephir init " . $extname);
            }
            $jsonpath=$namespacepath.'config.json';
            if(file_exists($jsonpath)){//对配置文件进行改写
                 $jsonmes=file_get_contents($jsonpath);
                 global $author,$version;
                 $jsonmes=str_replace('"author": ""','"author": "'.$author.'"',$jsonmes);
                 $jsonmes=str_replace('"unused-variable": true','"unused-variable": false',$jsonmes);
                 $jsonmes=str_replace('"nonexistent-constant": true','"nonexistent-constant": false',$jsonmes);
                //$jsonmes=str_replace('"nonexistent-function": true','"nonexistent-function": false',$jsonmes);
                 $jsonmes=str_replace('"nonexistent-class": true','"nonexistent-class": false',$jsonmes);
                 $jsonmes=str_replace('"version": "0.0.1"','"version": "'.$version.'"',$jsonmes);
                 $jsonmes=str_replace('"non-valid-objectupdate": true','"non-valid-objectupdate": false',$jsonmes);
                 $jsonmes=preg_replace_callback('@"extensions":\s*\[(.*)\]@',function($match)use($namespace){//对需要的模块进行修改
                     $arr=explode(',',trim($match[1]));
                     $narr=$namespace->getRequires();
                     $arr=array_merge($arr,$narr);
                     $arr=array_unique($arr);
                     $arr=trim(implode(',',$arr));
                     if($arr!=''&&$arr[0]==',')
                         $arr=substr($arr,1);
                     return '"extensions":['.$arr.']';
                 },$jsonmes);
                 file_put_contents($jsonpath,$jsonmes);
            }
        }
        foreach ($this->childs as $namespace) {
            $extname=explode('\\',$namespace->name)[0];
            echo($extname."导出完毕！" . NEWLINE);
            $namespacepath=$path.$extname.'/'.$namespace->name.'/';
            $namespacepath=str_replace('\\','/',$namespacepath);
            if(!file_exists($namespacepath))
                Util::MkDir($namespacepath);
            foreach ($namespace->childs as $classitems) {
                $filename=$namespacepath.$classitems->name.'.zep';
                $str=$classitems->parent->getmes($classitems->name);
                $str=preg_replace_callback('@"[\w\d_]+"[=!]{2,3}"[\w\d_]+"@',function($match){//把所有常量==进行转换
                     eval('$r='.$match[0].';');
                     return isset($r)&&$r===true;
                },$str);
                $str=PhpDocHelp::replaceWithoutString($str,function ($m){
//                    if(!preg_match('@function@',$m))
//                    $m=preg_replace_callback('@(!==|===)?\s*([\:\$\w\d_]+)@',function($mm){//对true和false进行转换
//                         if($mm[1]!='')return $mm[0];
//                         if($mm[2]=='true')return $mm[1].' 1';
//                         else if($mm[2]=='false')return $mm[1].' 0';
//                         return $mm[0];
//                    },$m);
                    return $m;
                    },function ($m,$pre,$next){//把所有的单字节string转char
                    $m=preg_replace('@[\\\]\'@','\'',$m);//把所有的\'转'
                    if(preg_match('@\]\s*[=!]{2,3}\s*$@',$pre)&&$next!='.')
                    $m=preg_replace('@^"(.)"$@','\'\1\'',$m);
                    return $m;
                });
                $str=PhpDocHelp::rmSpaceLine($str);
                //$str=iconv('utf-8','gb2312',$str);
                if(trim($str)!='')
                file_put_contents($filename,PhpDocHelp::utf8encoding($str));
            }
        }
    }
    public function isLeaf(){
        if($this->childs!=null&&count($this->childs)>0)
            return false;
        return true;
    }
    protected function initialze($name,$parent,$text){
        $this->childs = [];
        $this->name = trim($name);
        $this->parent = $parent;
        $this->text=$text;
        $this->prechilds=[];
        $this->endchilds=[];
        if($parent!=null)
        $this->level = $parent->level+1;
        else $this->level=0;
    }
    protected function getmes(){
        $mes='';
        if($this->prechilds!=null){
            foreach ($this->prechilds as $child) {
                $mes.=str_repeat(' ',$child->level-1).$child->getmes();
            }
        }
        if($this->childs!=null){
            foreach ($this->childs as $child) {
                $mes.=str_repeat(' ',$child->level-1).$child->getmes();
            }
        }
        if($this->endchilds!=null){
            foreach ($this->endchilds as $child) {
                $mes.=str_repeat(' ',$child->level-1).$child->getmes();
            }
        }
        return $mes;
    }
    protected function getparentfun(){
        $p=$this;
        while($p->level>3){
            $p=$p->parent;
        }
        return $p;
    }
    protected function getparentclass(){
        $p=$this;
        while($p->level>2){
            $p=$p->parent;
        }
        return $p;
    }
    protected function getparentnamespace(){
        return $this->getparentclass()->parent;
    }
    private function checkname($name){
        $name=str_replace('/','\\',strtolower($name));
        if($name!='')
        if($name[0]=='\\')$name=substr($name,1);
        if(substr($name,-1)=='\\')$name=substr($name,0,-1);
        return $name;
    }
    protected function getNameSpaceByName($name){
        $root=PhpRoot::$instance;
        foreach ($root->childs as $child) {
            if(strtolower($child->name)==$name)
                return $child;
        }
        if(PRINT_OUT)
        echo '没找到'.$name.'命名空间'.NEWLINE;
        return null;
    }
    protected function getClassByName($name){
        $root=$this;
        $name=PhpRoot::checkname($name);
        while(!$root instanceof PhpNode_NameSpace&&$root->parent!=null){
            $root=$root->parent;
        }
        foreach ($root->childs as $child) {
            if(strtolower($child->name)==$name)
                return $child;
        }
        if(PRINT_OUT)
        echo '没找到'.$name.'类'.NEWLINE;
        return null;
    }
    protected function getFunByName($name){
        $root=$this;
        $name=PhpRoot::checkname($name);
        while(!$root instanceof PhpNode_Class&&$root->parent!=null&&!$root instanceof PhpNode_NameSpace){
            $root=$root->parent;
        }
        foreach ($root->childs as $child) {
            if($root instanceof PhpNode_Class){
            if(strtolower($child->name)==$name)
                return $child;
            }else{
                foreach ($child->childs as $c) {
                    if(strtolower($c->name)==$name)
                        return $c;
                }
            }
        }
        if(PRINT_OUT)
        echo '没找到'.$name.'方法'.NEWLINE;
        return null;
    }
    protected function getClassByFullName($namespaceClassname){
        $root=$this;
        $namespaceClassname=PhpRoot::checkname($namespaceClassname);
        $arr=explode('\\',trim($namespaceClassname));
        $classname=end($arr);
        unset($arr[count($arr)-1]);
        $spacename=implode('\\',$arr);
        $r=$this->getNameSpaceByName($spacename);
        if($r!=null)
            $r=$r->getClassByName($classname);
        return $r;
    }
    protected function getFunByFullName($funname,$namespaceClassname){
        $r=$this->getClassByFullName($namespaceClassname);
        if($r!=null)
           $r=$r->getFunByName($funname);
        if($r!=null&&$r instanceof PhpNode_Function)
        return $r;
        return null;
    }
    protected function after($node){
        $arr=[];
        $node->level=$this->level;
        foreach ($this->parent->childs as $item) {
            $arr[]=$item;
            if($item==$this)
                $arr[]=$node;
        }
        if(!in_array($node,$arr)){$arr[]=$node;}
        $this->parent->childs=$arr;
    }
    protected function before($node){
         $arr=[];
        $node->level=$this->level;
         foreach ($this->parent->childs as $item) {
             if($item==$this)
                 $arr[]=$node;
             $arr[]=$item;
         }
        if(!in_array($node,$arr)){$arr[]=$node;}
         $this->parent->childs=$arr;

    }
    protected function append($node){
        $node->level=$this->level+1;
         $this->endchilds[]=$node;
    }
    protected function prepend($node){
        $node->level=$this->level+1;
         $this->prechilds[]=$node;
    }
    protected function start(){
        if($this->childs!=null)
            foreach ($this->childs as $child) {
                $child->start();
            }
    }
    protected function getInfo(){
        $fun=$this->getparentfun();
        return @$this->getparentnamespace()->name.'\\'.$this->getparentclass()->name.($fun!=null?'::'.$fun->name:"");
    }
}
class PhpNode_NameSpace extends PhpRoot {
    public $name;
    private static $ok;
    private $usespaces;
    private $requires;
    public function getRequires(){
        return $this->requires;
    }
    public function __construct($name,$parent,$text)
    {
        $this->initialze($name, $parent, $text);
        $this->usespaces=[];
        $namearr=explode('\\',$name);
        $firstname=$namearr[0];
        unset($namearr[0]);
        foreach ($namearr as $item) {
            if(preg_match('@'.$firstname.'$@',$item)){//对命名空间进行检查
                echo("$name 该命名空间中的{$firstname}和{$item}尾部重复,请修改名称!");
                fgets(STDIN);die;
            }
        }
    }
    public function Load($mes){
        self::$ok=true;
        $checkname=function ($name){
            if($this->childs!=null){
                foreach ($this->childs as $child) {
                    if($child->name==$name){
                          echo $name."该类重复定义";
                          fgets(STDIN);
                          return false;
                    }
                }
            }
            return true;
        };
        if($this->requires==null)$this->requires=[];
        $mes=PhpDocHelp::replaceWithoutString($mes,function ($m){//把类中所有的空间名称替换
            $m=preg_replace_callback('@use \s*([\w\d_\\\]*)\s*;@',function($match){//匹配命名空间的use
                $arr=explode('\\',$match[1]);
                $this->usespaces[$arr[count($arr)-1]]='\\'.$match[1];
            },$m);
            $m=preg_replace_callback('@(.?)([\w\d_]+)@',function($match){
                 if($match[1]=='$'||$match[1]=='\\'||$match[1]==':'){
                     return $match[0];
                 }
                 if(key_exists($match[2],$this->usespaces)){
                     $match[0]=$match[1].$this->usespaces[$match[2]];
                 }
                 if($match[0][0]=='\\'){
                     $extname=str_replace('\\','',$match[0]);
                     if(in_array($extname,get_loaded_extensions())){
                         $this->requires[]='"'.$extname.'"';
                     }
                 }
                 return $match[0];
            },$m);
            return $m;
        });
        $this->requires=array_unique($this->requires);
        while(self::$ok) {
            self::$ok=false;
            $mes = preg_replace_callback('@\s*((abstract |final )?)class ([\w\d\\\_]+)\s*([^{]*)({.*)@is', function ($match)use($checkname){
                $index = PhpDocHelp::splitIndex($match[5]);
                $class = substr($match[5], 0, $index);
                if(!$checkname($match[3])){die;}
                $this->childs[] = new PhpNode_Class($match[3],  $this, $class, $match[1],$match[4]);
                self::$ok=true;
                return substr($match[5], $index);
            }, $mes);
            $mes=preg_replace_callback('@\s*interface ([\w\d\\\_]*)\s*(([^{]*)?)\s*({.*)@is', function ($match)use($checkname){
                global $ok;
                $index = PhpDocHelp::splitIndex($match[4]);
                $class = substr($match[4], 0, $index);
                if(!$checkname($match[3])){die;}
                $this->childs[] = new PhpNode_Interface($match[1],  $this, $class, $match[2]);
                return substr($match[4], $index);
                self::$ok=true;
            }, $mes);
        }
        $mes=PhpDocHelp::rmSpaceLine($mes);
        if($this->text=='')
        $this->text=$mes;
        else if(!strpos($this->text,$mes)){
            $this->text.=$mes;
        }
    }
    protected function getmes($classname=''){
        $mes='';
        if($classname=='') {
            if($this->childs!=null){
                foreach ($this->childs as $child) {
                        $mes .= $child->getmes();
                    }
            }
        }
        else{
            if($this->childs!=null){
                foreach ($this->childs as $child) {
                    if($child->name==$classname) {
                        $mes = $child->getmes();break;
                    }
                }
            }
        }
        if($this->text!='')$this->text.=NEWLINE;
        if(trim($mes)=='')return '';
        return ($this->name!=''?'namespace '.$this->name.';'.NEWLINE:'').$this->text.$mes;
    }
}
class PhpNode_Class extends PhpRoot{
    public $extend;//类继承对象
    public $modifier;//类修饰符
    private $funstaticargs;//方法内部静态变量
    private $absfunctions;
    private $isinlineclass;//内部匿名方法转换的类
    public function isInline(){
        return $this->isinlineclass;
    }
    public function __construct($name,$parent,$text,$modifier,$extend,$isinlineclass=false)
    {
        $this->haveforeach=preg_match('@foreach\s*\(\s*\$this as@',$text);
        $this->isinlineclass=$isinlineclass;
        $text=PhpDocHelp::replaceWithoutString($text,function ($m)use($name,$extend,$parent){//把类中所有的空间名称替换
            if(preg_match('@__DIR__|__FILE__@',$m)){//检查__DIR__和__FILE__
                echo("在".$parent->name."\\$name 类中出现{$m[0]}");fgets(STDIN);die;
            }
//            $m=preg_replace_callback('@self::|parent::([\$\w\d_]+)@',function($match)use($name,$extend,$parent){
//                 if($match[0]=='self::')return '\\'.$parent->name.'\\'.$name.'::';
//                 else {
//                     if($match[1]=='__construct')
//                     return '$this->'.$match[1].'__';
//                     return trim(str_replace('extends', '', $extend)) . '::'.$match[1];
//                 }
//            },$m);
            return $m;
        });
        if(!preg_match('@[a-z]@',$name)){
             echo $parent->name.'\\'.$name ."该类名必须有一个小写字母\r\n";
             fgets(STDIN);
             die;
        }
        $this->funstaticargs=[];
        $this->absfunctions='';
        $this->initialze($name, $parent, $text);
        $this->modifier = $modifier;
        $this->extend = $extend;
        $text = substr($text, 1, -1);
        $GLOBALS['have'] = true;
        while ($GLOBALS['have']) {
            $GLOBALS['have'] = false;
            $text = preg_replace_callback('@([\w ]*abstract[\w ]*)function ([\w_]*)\s*\(([^{;]*)\)\s*;@si', function ($match)//类抽象方法处理
            {
                $this->absfunctions.=trim($match[0]).NEWLINE;
                $GLOBALS['have'] = true;
            }, $text);
            $text = preg_replace_callback('@([\w ]*)function ([\w_]*)\s*\(([^{]*)\)[^{]*([{;].*)@si', function ($match)//类方法处理
            {
                $index = PhpDocHelp::splitIndex($match[4],'}');
                $fun = substr($match[4], 0, $index);
                $this->childs[] = new PhpNode_Function($match[2], $this, $fun, $match[1], $match[3]);
                $GLOBALS['have'] = true;
                return substr($match[4], $index);
            }, $text);
            $text = preg_replace_callback('@((public +|protected +|static +|private +){1,2}|var )(\$[\S]*);@', function ($match) {
                $modifiers = explode(' ', trim($match[1]));
                if (count($modifiers) > 1 && trim($modifiers[0] == 'static')) $match[1] = $modifiers[1] . ' ' . $modifiers[0] . ' ';
                array_unshift($this->childs,new PhpNode_Member($match[3], $this, $match[0], $match[1]));
            }, $text);
        }
        $this->text = PhpDocHelp::rmSpaceLine($text);//表示不是方法和成员的内容
    }
    private $haveforeach=false;
    public function getextendclasses(&$arr=[]){
        if($this->extend){
            $p=preg_replace('@^extends\s*@','',$this->extend);
            if($p[0]!='\\'){
                $p='\\'.$this->getparentnamespace()->name.'\\'.$p;
            }
            $p=$this->getClassByFullName($p);
            if($p){
                $arr[]=$p;
                $p->getextendclasses($arr);
            }
            else if(PRINT_OUT){
                echo("没有找到".$this->extend."该类,继承类和子类要放在同一个插件路径里头!".NEWLINE);
            }
        }
        return $arr;
    }
    public function start()
    {
        parent::start();
        if($this->extend!=''||$this->haveforeach){
            $arr=$this->getextendclasses();
            $childs=$this->childs;
            foreach($arr as $c){
                $childs=array_merge($childs,$c->childs);
            }
            if(count($arr)>0){
                if(end($arr)->name=='ControlBase'){
                    $arr=[];
                    $havefun=[];
                    foreach($childs as $child){
                        if($child instanceof PhpNode_Function&&!$child->isStatic()&&$child->isPublic()&&$child->parameters){
                            $funname=strtolower($child->name);
                            if(in_array($funname,$havefun))continue;
                            if($child->getparentclass()->name=='ControlBase')continue;
                            $havefun[]=$funname;
                            $param=[];
                            foreach($child->parameters as $p){
                                $parr=preg_split('@=@',$p,2);
                                if(count($parr)<2)$v='null';
                                else $v=$parr[1];
                                $param[]=$v;
                            }
                            $param=$funname.'=>['.implode(',',$param).']';
                            $arr[]=$param;
                        }
                    }
                    $this->childs[]=new PhpNode_Function('__getfunargs',$this,'return ['.implode(',',$arr).'];','public','');
                }
            }
            $membmes=[];
            if(preg_match('@[\\\]ModelBase\s$@',$this->extend))
                $membmes[]='"id":$this->id';
            foreach ($childs as $item) {//遍历自身进行转换
                if($item instanceof PhpNode_Member&&!$item->isStatic()&&(!$item->isPrivate()||$item->name=='$id')){
                    $mes=substr($item->name,1);
                    $mes=explode('=',$mes);
                    $mes[1]='$this->'.$mes[0];
                    $mes[0]=trim($mes[0]);
                    $mes[0]='"'.$mes[0].'"';
                    $mes=implode(':',$mes);
                    if(!in_array($mes,$membmes))
                        $membmes[]=$mes;
                }
            }
            $this->childs[]=new PhpNode_Function('__getmems',$this,'return ['.implode(',',$membmes).'];','protected ','');
        }
    }
    public function setMemberPublic($name){
        $name=explode('::',$name);
        $name=end($name);
        foreach ($this->childs as $child) {
            if($child instanceof PhpNode_Member&&explode('=',$child->name)[0]==$name){
                $child->setPublic();
            }
        }
    }
    protected function getmes()
    {
        $mes=$this->getFunStaticArgs().$this->text.parent::getmes().$this->absfunctions;
        if(preg_match('@^\s*$@s',$mes))return"";
       return $this->modifier.' class '.$this->name.' '.$this->extend.'{'.NEWLINE.$mes.'}'.NEWLINE;
    }
    private function getFunStaticArgs(){
        if(count($this->funstaticargs)>0)
            return implode("\r\n",$this->funstaticargs);
    }
    public function insertFunStaticArgs($name){
         $name=trim($name);
         if(!in_array($name,$this->funstaticargs)){
             $this->funstaticargs[]=$name;
         }
    }
}
class PhpNode_Interface extends PhpRoot{
    private $extend;
    private $modifier;
    public function __construct($name,$parent,$text,$extend)
    {
        $this->initialze($name,$parent,$text);
        $this->extend = $extend;
         $text = preg_replace_callback('@[ \w]*\(.*\);@', function ($match) {
                if (!preg_match('@public|protected|private@', $match[0])) {
                    $match[0] = 'public ' . ltrim($match[0]);
                }
                return $match[0];
            }, $text);
            $this->text = substr($text, 1, -1);
    }
    protected function getmes()
    {
        return $this->modifier.' interface '.$this->name.' '.$this->extend.'{'.$this->text.parent::getmes().'}';
    }
}

class PhpNode_Member extends PhpRoot{//类的成员
    private $modifier;
    public function __construct($name,$parent,$text,$modifier)//$name 成员名称包括$,   $text 比如public $c;   $modifier 成员修饰符
    {
        $text=PhpDocHelp::replaceConst($text);
        $text=preg_replace('@"."@','',$text);
        $this->initialze($name,$parent,$text);
        $this->modifier = $modifier;
        if($this->isStatic())$this->setPublic();
        else  $this->text= str_replace('private ','protected ',$this->text);
    }
    public function isStatic(){
        return strpos($this->modifier,'static')!==false;
    }
    public function isPrivate(){
        return strpos($this->modifier,'private')!==false;
    }
    protected function getmes()
    {
       return $this->text.NEWLINE;
    }
    public function setPublic(){
        $this->text= str_replace('private ','public ',$this->text);
        $this->text= str_replace('protected ','public ',$this->text);
    }
}
class PhpNode_Inline extends PhpRoot{
    private $otherchilds;
    public function __construct($name,$parent,$text)//$text 有所有的内容
    {
        $this->initialze($name,$parent,$text);
        $this->text=PhpDocHelp::replaceWithoutString($text,function ($m){
//            $m=preg_replace_callback('@echo\s*\(?(.+)\)?\s*;@',function($match){//去掉echo的括号
//                if(preg_match('@[+\-*/]@',$match[1]))$match[1].='.""';
//                return 'echo '.$match[1].';';
//            },$m);
            if(preg_match('@(\$[\w\d_]*)\s*=\s*&\s*(\$[\w\d_]*)@',$m,$match)){//检查引用类型 比如$a=&$b;
                echo($match[0].'引用赋值不支持'.NEWLINE);fgets(STDIN);die;
            }
            $m=preg_replace_callback('@^print([ (])@',function($match){//不支持print
                 return "echo".$match[1];
            },$m);
            $m=preg_replace_callback('@([\$\w\d_\\\>\-:]+)\*\*([\$\w\d_\\\>\-:]+)|([\d\.]+)\*\*([\d\.]+)@',function($match){//对**进行支持
                if(count($match)>3){
                    $match[1]=$match[3];
                    $match[2]=$match[4];
                }
                 return "pow({$match[1]},{$match[2]})";
            } ,$m);
            return $m;
        });
            $this->text = preg_replace_callback('@^\s*([\S]+)\s*([\.+\-*/%^\|&])=(.*);@s', function ($match) {//对对象的赋值bug进行修复
                return 'let ' . $match[1] . '=' . $match[1] . $match[2] . '(' . $match[3] . ');';
            }, $this->text);
            $this->text = preg_replace_callback('@^(.*?)([\.])=@s', function ($match) {//对对象的赋值bug进行修复
                return 'let ' . $match[1] . '=' . $match[1] . '.';
            }, $this->text);
        if(preg_match_all('@:?(\$[\w\d_]+)\s*?=@',$this->text,$match))//对所有的临时变量进行匹配
            for($i=0;$i<count($match[1]);$i++){
                 $name=$match[1][$i];
                if($match[0][$i][0]!=':') {
                    $this->getparentfun()->insertTempValue($name, str_replace('let ', '', $this->text));
                    $this->text=preg_replace('@\\'.$match[1][$i].'@',$name,$this->text,1);
                }
            }
    }
    public static function checkStaticFun($mes,$thisobj){
        $mes= preg_replace_callback('@([\\\_\w\d]+)::([\w\d_]+)\(([^;]*)\)\s*@',function($match)use($thisobj){//对不存在的静态方法调用进行匹配
            if(preg_match('@^(self::|parent::)@',$match[0]))return $match[0];
            $index=PhpDocHelp::splitIndex($match[0],')');
            $spacename=$match[1];
            if ($spacename[0] != '\\') {
                $spacename = '\\' . $thisobj->getparentnamespace()->name . '\\' . $spacename;
            }
            $r=null;
            if(explode('\\',substr($spacename,1))[0]==explode('\\',$thisobj->getparentnamespace()->name)[0])
            $r = $thisobj->getFunByFullName($match[2],$spacename);
            if ($r == null) {
                $start=strlen($match[1].'::'.$match[2].'(');
                $match[3]=trim(substr($match[0],$start,$index-$start-1));
                $match[3]=',['.$match[3].']';
                if(PRINT_OUT)
                    echo("没有找到静态函数$match[0],后面如果有使用返回值当成参数可能需要强转！".NEWLINE);
                return 'call_user_func_array("'.str_replace('\\','\\\\',$spacename).'::'.$match[2].'"'.$match[3].')'.substr($match[0],$index);
            }
            return $match[0];
        },$mes);
        return $mes;
    }
    protected function getmes()
    {
        $mes=trim($this->text);
        if(preg_match('@->[^\$]->@',$mes)){
            echo($this->getInfo()."    {$mes}不支持->的连锁调用".NEWLINE);
            fgets(STDIN);die;
        }
        if($this->getparentclass()->isInline())//把匿名方法内变量当成方法使用
        $mes=preg_replace_callback('@\$this->([\w_]+)\s*\(([^;)]*)\)@',function($match){
             $fun=$this->getparentclass()->getFunByName($match[1]);
             if($fun===null){
                 return "call_user_func_array(\$this->$match[1],[$match[2]])";
             }
        },$mes);
        if(preg_match('@^continue\s*;$@',$mes)){
            $parent=$this->parent;
            while(true){
                if($parent==null||$parent->parent==null)break;
                if($parent instanceof PhpNode_Condiction){
                    if($parent->continue!=''){
                        break;
                    }
                }
                $parent=$parent->parent;
            }
            if($parent!=null&&$parent instanceof PhpNode_Condiction){
                if($parent->continue!=';')
                return $parent->continue.$mes;
                else return $mes;
            }
        }
        $case='';
        if(preg_match('@^((case [^:]+:\s*){1,})(.*)@s',$mes,$m)){
            $case=$m[1];
            $mes=$m[3];
        }
        $mes=self::checkStaticFun($mes,$this);
        $mes=preg_replace_callback('@(\$[\w\d_]+)\s*\(@',function($match){//把变量当做方法调用的时候重写
            return '{'.$match[1].'}(';
        },$mes);
        $mes=preg_replace_callback('@new (\$[\w\d_]+)\s*\(?[^;]*\)?\s*;@',function($match){//把变量声明的类重写
            return 'new {'.$match[1].'}('.((count($match)>2)?$match[2]:'').');';
        },$mes);
        $mes=preg_replace_callback('@new ([\w\d_]+)@',function($match){//类名补充完整重写
            return 'new \\'.$this->getparentnamespace()->name.'\\'.$match[1];
        },$mes);
        $mes=preg_replace_callback('@->(\$[\w\d_]+)@',function($match){//把变量声明的重写
            return '->{'.$match[1].'}';
        },$mes);
        if(PhpDocHelp::getVarType($mes)=='array'||preg_match('@[^\w\d_]array\(@',$mes)||preg_match('@\[(["\s\w_ ]+=>["\s\w_ ]+,?)+\]@',$mes)){//判断是否为数组
            $mes = preg_replace_callback('@array[ ]*\(|\)\s*([,;])@', function ($match) {
                if(count($match)<2)return '[';
                return ']'.$match[1];
            }, $mes);
            while(preg_match('@(\]\s*),(\s*\])@',$mes,$match)){//对数组最后一个逗号进行处理
                $mes=str_replace($match[0],$match[1].$match[2],$mes);
            }
            $mes=str_replace('=>',':',$mes);
            if(preg_match('@\$[\w\d_]+\s*=\s*\[@',$mes)){
                $mes='let '.$mes;
            }
        }
            $mes = preg_replace_callback('@^\$(\$[\w\d_])+\s*=(.*);@', function ($match) {//对$$a=进行转换
                return 'let {' . $match[1] . '}=' . $match[2] . ';';
            }, $mes);
           if (preg_match('@^\$[\S]+\s*[\.+\-*/%]?=|[\S]+::[\S ]+?\s*[\.+\-*/%]?=@', $mes)) {//对$a=进行转换
                $mes=trim($mes);
                if(preg_match('@::(.*?)=\s*""\s*;@',$mes,$m)){
                    echo("{$this->getInfo()}中$m[1],静态变量不能用空字符串赋值".NEWLINE);fgets(STDIN);die;
                }
                if(substr($mes,-1)==';'){//表示是普通语句
                    if(!preg_match('@^\s*return@',$mes))
                    $mes = 'let ' . $mes;
                }
                else{//表示是for循环里头的
                    $arr = explode(',', $mes);
                    if (count($arr) > 1) {
                        $mes = '';
                        foreach ($arr as $item) {
                            if (substr($item, -1) != ';') $item .= ';';
                            $mes .= 'let ' . $item;
                        }
                    } else {
                        $arr = explode('=', $mes);
                        if (count($arr) < 3)
                            $mes = 'let ' . $mes;
                        else {
                            $mes = '';
                            $r = end($arr);
                            for ($i = 0; $i < count($arr) - 1; $i++) {
                                $mes .= 'let ' . $arr[$i] . '=' . $r;
                            }
                        }
                    }
                }
            }
            $mes=PhpDocHelp::replaceWithoutString($mes,function($mes){
                if(preg_match_all('@(\+\+|\-\-)\s*([\w\d_\\\]+::)?\s*(\$[\w\d_\->]+)\s*;@',$mes,$match)) {//对前置++ --进行转换
                    $nmes=$mes;
                    $mes='';
                    for($i=0;$i<count($match[0]);$i++){
                        $m=$match[2][$i].$match[3][$i];
                        if(count($match[0])>1){
                            $nmes=str_replace($match[0][$i],$m,$nmes);
                        }
                        else{
                            $nmes='';
                        }
                        $mes.='let '.$m."={$m}{$match[1][$i][0]}(1);";
                    }
                    $nmes=trim($nmes);
                    if($nmes!=''){
                        $nmes='let '.$nmes.';';
                    }
                    $mes=$nmes.$mes;
                }
                if(preg_match_all('@([\w\d_\\\]+::)?(\$[^;,]+?)\s*(\+\+|\-\-)\s*;@',$mes,$match)) {//对后置++ --进行转换
                    $nmes=$mes;
                    $mes='';
                    for($i=0;$i<count($match[0]);$i++){
                        $m=$match[1][$i].$match[2][$i];
                        if(count($match[0])>1){
                            $nmes=str_replace($match[0][$i],$m,$nmes);
                        }
                        else{
                            $nmes='';
                        }
                        $mes.='let '.$m."={$m}{$match[3][$i][0]}(1);";
                    }
                    $nmes=trim($nmes);
                    if($nmes!=''){
                        $nmes='let '.$nmes.';';
                    }
                    $mes=$nmes.$mes;
                }
                return $mes;
            });
        $mes=preg_replace_callback('@(let ){2,}@',function($match){//对let重复进行去除
            if(PRINT_OUT)
            echo('程序出现let重复！');
            return "let ";
        },$mes);
        return '  '.$case.$mes.NEWLINE;
    }
    protected function start()
    {
        parent::start();
        if(preg_match('@^([^=]*\$[\w\d_]+\s*=)?(\s*[\$:\->\w\d_\\\]+)\s*\((.*)\);@s',$this->text,$match)){//对方法调用匹配
            if(!preg_match('@new\s*[\w\d_\\\]@',$this->text,$m)) {
//                if(preg_match('@use\s*\(|&\$[\w\d_]@',$match[3])){
//                    var_dump($match[0]);
//                    echo('匿名方法作为参数传递时不能用&和use'.NEWLINE);
//                    fgets(STDIN);die;
//                }
                if ($match[1] != '') $match[1] = 'let ' . $match[1];
                $child = new PhpNode_FunctionArgs($match[2], $this, $match[3],$match[1]);
                $this->text = $child->getmes();
            }
        }
    }
}
class PhpNode_Condiction extends PhpRoot{
    public $continue;
    private $args;
    private $isForeach;
    public function __construct($name,$parent,$text,$args)//$name 为EMPTYNODE的时候表示最顶层
    {
        $this->continue='';
        $args=PhpDocHelp::replaceWithoutString($args,function ($m){ //符号<>不支持
            return preg_replace('@<>@','!=',$m);
        });
        if(trim($name)=='else if'){
            $name='elseif';
        }
        $this->isForeach=$name=='foreach';
        $this->initialze($name, $parent, $text);
        $this->args = $args;
        if ($text != '' && $text[0] == '{')
            $text = substr($text, 1, -1);
        while ($text != '') {
            $text = trim($text);
            if (substr($text, 0, 2) == 'do') {
                $index = PhpDocHelp::splitIndex($text, ';');
            } else
                $index = PhpDocHelp::splitIndex($text, ';', '}');
            $block = trim(substr($text, 0, $index));
            if (preg_match('@^(do)\s*\{(.*?)\}\s*while\s*\((.*)\);@is', $block, $match)) {//处理do循环
                $this->childs[] = new PhpNode_Condiction($match[1], $this, $match[2], $match[3]);
            } else if (preg_match('@^(if|else if|foreach|while|for|switch)\s*\((.+?)\)\s*\{(.*)\}@is', $block, $match)) {
                if (substr($match[3], -1) == '}') $match[3] = substr($match[3], 0, -1);
                $this->childs[] = new PhpNode_Condiction($match[1], $this, $match[3], $match[2]);
            } else if (substr($block, -1) == ';' && preg_match('@^(if|else if|foreach|while|for|switch)(.+)@is', $block, $match)) {
                $i = PhpDocHelp::splitIndex($match[2], ')');
                $args = substr(trim(substr($match[2], 0, $i)), 1, -1);
                $main = substr($match[2], $i);
                $this->childs[] = new PhpNode_Condiction($match[1], $this, $main, $args);
            } else if (preg_match('@^else\s*?\{?(.*)\}?@s', $block, $match)) {
                $match[1] = trim($match[1]);
                if (substr($match[1], -1) == '}') {
                    $match[1] = substr($match[1], 0, -1);
                    if ($match[1][0] == '{') $match[1] = substr($match[1], 1);
                }
                $this->childs[] = new PhpNode_Condiction('else', $this, $match[1], '');
            } else {
                $this->childs[] = new PhpNode_Function(EMPTYNODE, $this, $block,'','');
            }
            $text = substr($text, $index);
        }
    }
    protected function start()
    {
         parent::start();
        if(preg_match('@\((.*)\)@',$this->args,$m)){
            preg_replace_callback('@\$[\w\d_]+@',function($match){
                $this->getparentfun()->insertTempValue($match[0],'var');
            },$m[1]);
        }
        switch ($this->name){
            case 'for':
                $argarr=explode(';',$this->args);
                foreach (explode(',',$argarr[0]) as $item) {
                    $this->before(new PhpNode_Inline('',$this->parent,$item.';'));
                }
                if(preg_match('@[+-]{2}@',$argarr[1])){echo('循环内条件不允许使用++，--!'.NEWLINE.$this->getInfo().NEWLINE.$this->getmes().NEWLINE);return;}
                $this->prepend(new PhpNode_Condiction('if',$this,'break;','!('.$argarr[1].')'));
                foreach (explode(',',$argarr[2]) as $item) {
                    $this->append(new PhpNode_Inline('',$this,$item.';'));
                }
                $this->name='loop';$this->args='';
                break;
            case 'do':
            case 'while':
            case 'if':
            case 'elseif':
                if(preg_match_all('@(([+-])\2\$[\w\d_]+)@',$this->args,$match)){
                     foreach ($match[0] as $m) {
                         $this->args=str_replace($m,substr($m,2),$this->args);
                         $this->prepend(new PhpNode_Inline('',$this,$m.';'));
                    }
                }
                $thisfun=[];
                if(preg_match_all('@(\$[\w\d_]+)([+-])\2@',$this->args,$match)){
                    foreach ($match[0] as $m) {
                        $this->args=str_replace($m,substr($m,0,-2),$this->args);
                        $this->continue.=(new PhpNode_Inline('',$this,$m.';'))->getmes();
                        $thisfun[]=function($obj,$m){$this->append(new PhpNode_Inline('',$this,$m.';'));};
                    }
                 ;}
                 if($this->name=='if'||$this->name=='elseif'||$this->name=='else'){$this->continue='';}
                 else{
                     if($this->continue=='')$this->continue=';';
                     if($this->name=='while')
                         $this->prepend(new PhpNode_Condiction('if',$this,'break;','!('.$this->args.')'));
                     else $this->append(new PhpNode_Condiction('if',$this,'break;','!('.$this->args.')'));
                     foreach ($thisfun as $item) {
                         $item($this,$m);
                     }
                     $this->text='';
                     $this->name='loop';$this->args='';
                 }
                break;
            case 'foreach':
                $this->args=preg_replace_callback('@\$this as@',function($match){
                    return '$this->__getmems() as';
                },$this->args);
                preg_match('@^(.+)\s*as\s*(.+)$@',$this->args,$match);
                $this->args=((new PhpNode_Inline('',$this,$match[1]))->getmes());
                $vars=explode('=>',$match[2]);
                foreach ($vars as $var) {
                    $var=trim($var);
                    if($var!='') $this->getparentfun()->insertTempValue($var,'var');
                }
                $this->name="for ".implode(',',$vars).' in ';
               break;
        }

    }
    protected function getmes()
    {
        if($this->name==EMPTYNODE)//空节点
          return parent::getmes();
        if($this->args!=''){
            $this->args=PhpNode_Inline::checkStaticFun($this->args,$this);
            $this->args='('.$this->args.')';
        }
        $mes=parent::getmes().str_repeat(' ',$this->level+2);
        if(trim($mes)=='')$mes=';';
        return $this->name.$this->args.'{'.NEWLINE.$mes.'}'.NEWLINE;
    }
}
class PhpNode_Function extends PhpRoot{
    public $modifier;
    public $parameters;//原始参数,为数组，且不包含&
    public $refindex;//引用位置，为数组
    protected $tempvalue;//内部变量
    protected $staticargs;//内部静态变量
    protected $inlinefunnames;//function a(){}这样直接声明的名字
    private $strongtype;//已经明确的方法变量类型
    private $actions;//建立完成后执行的方法
    public function __construct($name,$parent,$text,$modifier,$parameters)//$parameters 方法参数,不包括最外层的括号
    {
        $text=PhpDocHelp::replaceWithoutString($text,function ($m){
            $m=preg_replace_callback('@-\s*(\d+)@',function($match){//对-数字的bug进行修复
                return '-('.$match[1].')';
            },$m);
            $m=preg_replace_callback('@isset\s*\(\s*(\$[\w\d_]+)\s*\)@',function($match){//对isset($a)这样的局部变量不支持进行转换
                return "(!empty $match[1])";
            },$m);
            return $m;
        });
        $text=PhpDocHelp::replaceConst($text);
//        $root=str_replace('\\','/',$_SERVER["DOCUMENT_ROOT"]);
//        $text=preg_replace_callback('@'.$root.'@',function($match){
//            return '".$_SERVER["DOCUMENT_ROOT"]."';
//        },$text);
        $this->retypes=[];
        $this->actions=[];
        $this->runtemptype=[];
        $this->initialze($name, $parent, $text);
        $this->tempvalue=[];
        $this->refindex=[];
        $newparames=[REFTEMPOBJ];
        $parameters=trim($parameters);
        $parameters = explode(',', $parameters);
        $this->strongtype=[];
        if($parameters!=null&&$parameters[0]!=''&&$name!=EMPTYNODE) {//对参数引用进行处理
            for ($i = 0; $i < count($parameters); $i++) {
                if($parameters[$i]=='')continue;
                preg_match('@(&?)(\$[\w\d_]+)\s*(=[^,]+)?@',$parameters[$i],$match);
                $p = trim($match[1].$match[2].(count($match)>3?$match[3]:""));
                $typearr=explode(' ',trim($parameters[$i]));
                if(count($typearr)>1){
                    $this->strongtype[$i]=$typearr[0];
                }
                if ($p[0] == '&') {
                    $this->refindex[] = $i;
                    $parameters[$i] = substr($p, 1);
                } else {
                    $newparames[] = $p;
                    $parameters[$i]=$p;
                }
            }
            if (count($this->refindex) > 0&&!$this->getparentclass()->isInline()) {
                $this->after(new PhpNode_Function($name,$parent,$text,$modifier,implode(',', $parameters)));
                $this->parameters=$parameters;
                $text=preg_replace_callback('@(\$this->|self::|[\w_\\\]+::)([\w_]+)\s*\((.*?)\)\s*;@',function($match)use($name,$parameters){
                    if(strpos($match[1],'\\')!==false){
                        $n=str_replace('\\','',$match[1]);
                        $ncn=$this->getparentnamespace()->name.$this->getparentclass()->name.'::';
                        $ncn=str_replace('\\','',$ncn);
                        if($ncn!=$n)return $match[0];
                    }
                    if($match[2]==$name){
                        $arr=PhpDocHelp::splitArgs($match[3]);
                       if(count($arr)==count($parameters)){
                           $ok=true;
                           for($i=0;$i<count($parameters);$i++){
                               if(in_array($i,$this->refindex)){
                                     if(trim($arr[$i])!=trim($parameters[$i])){
                                         $ok=false;
                                     }
                                     $arr[$i]=REFTEMPOBJ;
                               }
                           }
                           if($ok)
                           return $match[1].$match[2].'('.implode(',',$arr).');';
                       }
                       echo($this->getInfo().'递归时如果使用引用参数不能更改递归时的变量!'.NEWLINE);fgets(STDIN); die;
                    }
                    return $match[0];
                },$text);
                $text=preg_replace_callback('@(.?)(\$[\w\d_]+)@',function($match){
                   if($match[1]!=':')
                   for($i=0;$i<count($this->parameters);$i++){
                       $pname=explode('=',$this->parameters[$i])[0];
                       if($match[2]==$pname&&in_array($i,$this->refindex))
                           return $match[1].REFTEMPOBJ.'->'.substr($match[2],1);
                   }
                   return $match[0];
                },$text);
                $this->name='ref__'.$this->name;
                $this->parameters=$newparames;
            }
            else $this->parameters=$parameters;
        }
        global $keywords;
        if($this->parameters)
        foreach($this->parameters as $p){
            preg_match('@\$([\w_]+)@',$p,$m);
            $p=$m[1];
            if(in_array($p,$keywords)){
                echo($this->getInfo()."中的\${$p}方法变量不能用$+关键字命名");fgets(STDIN);die;
            }
        }
        $this->modifier = trim($modifier);
        if($this->level==3&&$this->modifier=='')$this->modifier='public';
        $this->staticargs=[];
        $this->inlinefunnames=[];
        if ($text != '' && $text[0] == '{')
            $text = substr($text, 1, -1);
        while (trim($text) != '') {
            $text = trim($text);
            if($text[0]=='@'){
                echo($this->getparentclass()->name.'类'.$this->name.'方法中的@符号被忽略.'.NEWLINE);
                $text=substr($text,1);
            }
            if(preg_match('@^function\s+([\w\d_]+)@',$text,$m)){
                echo($this->getInfo()."不支持方法内部定义{$m[1]}直接方法!");
                //$index = PhpDocHelp::splitIndex($text, '}');
            }
            else if (substr($text, 0, 2) == 'do') {//do循环结尾
                $index = PhpDocHelp::splitIndex($text, ';');
            } else if(substr($text,0,3)=='try'){
                $index=PhpDocHelp::splitIndex($text,';');
            }
            else if (preg_match('@^if|else|foreach|while|for|switch|else@', $text)) {//判定语句循环结尾
                $index = PhpDocHelp::splitIndex($text, ';', '}');
            } else
                $index = PhpDocHelp::splitIndex($text, ';');
             $block = trim(substr($text, 0, $index));
             $text = substr($text, $index);
            if(count($this->inlinefunnames)>0){
                 $block=preg_replace_callback('@(\$?[\w_]+)\s*\((.*)\)\s*;@',function($match)use(&$text){//对方法内直接声明的方法名称变化
                    if (key_exists($match[1], $this->inlinefunnames)) {
                        $nmes=$this->inlinefunnames[$match[1]];
                        $match[2]=trim($match[2]);
                        $nmes=preg_replace('@%1@',$match[2],$nmes);
                        if(strpos($nmes,'%2')!==false){
                            $name=substr($nmes,0,strpos($nmes,'('));
                            preg_replace_callback('@&?\$([\w_]+)@',function($match)use(&$arr,&$nmes,$name,&$text){
                                if($match[0][0]=='&'){
                                    $match[0]=substr($match[0],1);
                                    $text=$match[0].'='.$name.'->'.$match[1].';'.NEWLINE.$text;
                                }
                                $this->childs[]=new PhpNode_Inline('',$this,$name.'->'.$match[1].'='.$match[0].';');
                            },explode('%2',$nmes)[1]);
                            $nmes=preg_replace('@%2[^\)]+@',$match[2],$nmes).';'.NEWLINE;
                        }
                        else $nmes.=';';
                        return $nmes ;
                    }
                    return $match[0];
                },$block);
            }
            if (preg_match('@^(if|else if|foreach|while|for|switch|else|do).*@is', $block, $match)) {
                $this->childs[] = new PhpNode_Condiction(EMPTYNODE, $this, $block, '');
            }
            else if(preg_match('@^try\s*\{(.*)\}\s*catch\s*\((.*?)\)\s*(\{.*)@is',$block,$match)) {//匹配try catch方法
                 $houindex = PhpDocHelp::splitIndex($match[3], '}');
                 $catchmes = substr($match[3], 0, $houindex);
                 $catchmes=substr(trim($catchmes),1,-1);
                 $shenyumes=trim(substr($match[3],$houindex));
                 $finallymes='';
                 if(substr($shenyumes,0,7)=='finally'){
                     $houindex = PhpDocHelp::splitIndex($shenyumes, '}');
                     $finallymes = substr($shenyumes, 0, $houindex);
                     $finallymes=preg_replace('@^finally\s*\{(.*)\}@s','\1',$finallymes);
                     $shenyumes=trim(substr($shenyumes,$houindex));
                 }
                 $this->childs[] = new PhpNode_TryCatch('try', $this, $match[1], $match[2], $catchmes,$finallymes);
                 $text =$shenyumes.$text;
             }
            else if(preg_match('@(^\$[\w\d_]+\s*=)?\s*\(?\s*\((.*)\)\(([^{]*)\)\s*;@s',$block,$match)&&strpos($block,'function')!==false){//匹配匿名方法调用
                echo($this->getparentclass()->name."类".$this->name."方法中，不支持匿名方法直接调用。".NEWLINE.$block.NEWLINE);
                fgets(STDIN);die;
            }
            else if(preg_match('@^\s*function ([\w\d_]+)\s*\(([^{]*)\)\s*\{(.*)\}\s*$|^([ \$\w\d_=]+)\s*=\s*\(?\s*function\s*\(([^{]*)\)\s*\{(.*)\}\s*\)?\s*;\s*$@s',$block,$match)){//匿名方法匹配
                if(count($match)>5){array_shift($match);array_shift($match);array_shift($match);}
                $args=preg_split('@\)\s*use\s*\(@',$match[2]);
                if(strpos($args[0],'&')!==false){
                    echo($this->getInfo()."里头的匿名方法不能使用引用参数!".NEWLINE);fgets(STDIN);die;
                }
                $funname=explode('=',trim($match[1]));
                for($i=0;$i<count($funname);$i++)$funname[$i]=trim($funname[$i]);
                if($funname[0][0]=='$')$match[1]=substr($funname[0],1);
                else $match[1]=$funname[0];
                $match[1]=trim($match[1]);
                $use='';
                $nargs=[];
                foreach ($args as $arg) {
                    if(trim($arg)!=''){
                        $arg=trim($arg);
                        $nargs[]=$arg;
                    }
                }
                if(count($nargs)>1)$use='%2'.$nargs[1];else $use='%1';
                foreach ($funname as $item) {
                    $this->inlinefunnames[$item]='$'.$match[1]."({$use})";
                }
                $match[2]=$match[3];
                $match[3]=implode(',',$nargs);
                if(substr($match[3],-1)==',')$match[3]=substr($match[3],0,-1);
                $classname='Closure__'.$this->name.$match[1];
                $fuzhi='';
                $match[2]=preg_replace_callback('@\$this\s*->([\w\d_]+)@',function($m){
                    $pc=$this->getparentclass();
                    $this->actions[]= function()use($pc,$m){
                        $pc->setMemberPublic('$'.$m[1]);
                    };
                    return '$neithis->'.$m[1];
                },$match[2]);
                if(count($nargs)<1)$nargs[0]='';
                if(count($nargs)<2)$nargs[1]='';
                else{
                    $usearr=explode(',',$nargs[1]);
                    $funarr=explode(',',str_replace('&','',$nargs[0]));
                    $fuzhi="";
                    $allarr=array_merge($funarr,$usearr);
                    for($i=0;$i<count($allarr);$i++){
                        $oldpar=str_replace('&','',$allarr[$i]);
                        $oldpar=explode('=',$oldpar)[0];
                        if(in_array($allarr[$i],$funarr))
                        $fuzhi.="if($oldpar!=null){\$this->".substr($oldpar,1)."=".$oldpar.';}'.NEWLINE;
                        $match[2]=preg_replace_callback('@(.?)\\'.$oldpar.'([^\w\d_])@',function($m)use($i,$oldpar){
                             if($m[1]==':')return $m[0];
                             return $m[1]."\$this->".substr($oldpar,1).$m[2];
                        },$match[2]);
                    }
                }
                $membermes='';
                if(isset($allarr))
                foreach ($allarr as $item) {
                    $membermes.='public '.str_replace('&','',$item).';'.NEWLINE;
                }
                if(trim($nargs[0])!=''){$nargs[1]=','.str_replace('&','',$nargs[1]);}else $nargs[1]='';
                if(trim($nargs[1]==','))$nargs[1]='';
                $firstarg=($this->getparentfun()->isStatic()?'null':'$this');
                $this->childs[]=new PhpNode_Inline('',$this,"\${$match[1]}=new {$classname}($firstarg{$nargs[1]});");
                $constancemes='';
                foreach (explode(',',$nargs[1]) as $item) {
                    if(trim($item)=='')continue;
                    $constancemes.='$this->'.str_replace('$','',$item).'='.$item.';'.NEWLINE;
                }
                if($nargs[0]!='')$nargs[0].=',';
                $nargs[0].='$_a_0=null,$_b_0=null';
                $classmes=<<<ETO
              {$membermes}
              private \$neithis;
              public function __construct(\$neithis{$nargs[1]}){
                \$this->neithis=\$neithis;
                {$constancemes}
              }
              public function __invoke({$nargs[0]}){
                   \$neithis=\$this->neithis;
                   {$fuzhi}
                  {$match[2]}
              } 
ETO;
                $node=new PhpNode_Class($classname, $this->getparentnamespace(),$classmes,'','',true);
                $this->getparentnamespace()->childs[]= $node;
            }
            else {
                if(preg_match('@^(\$[\w\d_]+\s*=\s*){2,}\s*(.)@',$block,$match)){//对连续赋值进行切割
                    if($match[2]!='=') {
                        $arr = explode('=', $match[0]);
                        $first = $arr[0];
                        $block = str_replace($match[0], $first . '=', $block);
                        array_shift($arr);
                        foreach ($arr as $item) {
                            if (trim($item) != '')
                                $text = $item . '=' . $first . ';' . NEWLINE . $text;
                        }
                    }
                }
                if(preg_match('@^list\((.*?)\)\s*=(.*)@s',$block,$match)){//对list()=进行转换
                    $arr=explode(',',$match[1]);
                    $first=trim($arr[0]);
                    $block=$first.'='.$match[2];
                    array_shift($arr);
                    $i=1;
                    foreach ($arr as $item) {
                        if(trim($item)!=''){
                            $text=$item.'='.$first.'['.$i++.'];'.NEWLINE.$text;}
                    }
                    $text.=$first.'='.$first.'[0];'.NEWLINE;
                }
                if (substr($block, 0, 6) == 'static') {//对方法内部静态变量进行处理
                    $block = trim(substr($block, 6));
                    preg_match_all('@\$([\w\d_]+)(=[^,;]+)?@', $block, $staticmath);
                    for ($i = 0; $i < count($staticmath[0]); $i++) {
                        $sname = trim($staticmath[1][$i]);
                        $svalue = $staticmath[2][$i];
                        if (!key_exists('$' . $sname, $this->staticargs)) {
                            $this->staticargs['$' . $sname] = '$__' . $sname . $name;
                            $this->getparentclass()->insertFunStaticArgs('private static $__' . $sname . $name . $svalue . ';');
                        }
                    }
                }
                else if(preg_match('@^([^=]*\$[\w\d_]+\s*=)?(\s*[\$:\->\w\d_\\\]+)\s*\((.*)(function\s*\(.*)\);@s',$block,$match)){//方法参数为匿名函数
                     $fun=PhpDocHelp::splitIndex($match[4],'}');
                     $fun=substr($match[4],0,$fun);
                     $funname='$inline_f'.PhpDocHelp::getid();
                     $match[0]=str_replace($fun,$funname,$match[0]);
                     $refmes='';
                     preg_replace_callback('@&\$([\w\d_]+)@',function($m)use(&$refmes,$funname){
                          $refmes.="\${$m[1]}={$funname}->{$m[1]};";
                     },$fun);
                     $text=$funname.'='.$fun.';'.NEWLINE.$match[0].$refmes.$text;
                }
                else {
                    $this->childs[] = new PhpNode_Inline('', $this, $block);
                }
            }
            if(count($this->staticargs)>0){
                $text=PhpDocHelp::replaceWithoutString($text,function ($str){//对方法内静态变量名称变化
                    $str=preg_replace_callback('@(.?)(\$[\w\d_]*)@',function($match){
                        if($match[0][0]!=':') {
                            if (key_exists($match[2], $this->staticargs)) {
                                return $match[1].$this->getparentclass()->name.'::' . $this->staticargs[$match[2]];
                            }
                        }
                        return $match[0];
                    },$str);
                    return $str;
                });}
        }
    }
    public function isPublic(){
        return strpos($this->modifier,'private')===false&&strpos($this->modifier,'protected')===false;
    }
    public function isStatic(){
        return strpos($this->modifier,'static')!==false;
    }
    public function getRunType($name){//获得方法内部变量类型
        $root=$this;
        while($root->parent!=null&&!$root->parent instanceof PhpNode_Class){
            $root=$root->parent;
            //if($root instanceof PhpNode_NiMingFunc)break;
        }
        if(key_exists($name,$root->tempvalue))
            return str_replace('_','',$root->tempvalue[$name]);
        return 'none';
    }
    private $argstype;//传入参数的类型
    public function insertArgsType($type){
         if(strpos($this->name,'__')!==false)return;
         if($this->argstype==null)$this->argstype=$type;
         else{
             for($i=0;$i<count($this->argstype);$i++){
                 if(!key_exists($i,$type))break;
                 if($this->argstype[$i]!=$type[$i]) $this->argstype[$i]='var';
             }
         }
    }
    public function insertTempValue(&$name,$type)
    {//对方法内部未声明的局部变量进行处理
        global $keywords,$super;
        $name=trim($name);
        $name=explode('->',$name)[0];
        if($name=='$this')return;
        if(in_array(substr($name,1),$keywords)){
            echo($this->getInfo()."中的{$name}变量不能用$+关键字命名");fgets(STDIN);die;
        }
        if(in_array($name,$super))return;
        $root = $this;
        if($type!='ref')
        $type = PhpDocHelp::getVarType($type);
        while ($root->parent != null && !$root->parent instanceof PhpNode_Class) {
            $root = $root->parent;
            //if($root instanceof PhpNode_NiMingFunc)break;
        }
        if ($root->parameters != null) {
           foreach ($root->parameters as $parameter) {
                 if($name==explode('=',$parameter)[0]){
                     return;
                 }
            }
        }
        if (key_exists($name, $root->tempvalue)) {
//            if(!in_array($root->tempvalue[$name],['string'])&&$type=="string"){
//                echo($this->getInfo().NEWLINE.$name."该变量不能变化类型，请换个名字!".NEWLINE.$text);fgets(STDIN);die;
//            }
            $oldtype=$root->tempvalue[$name];
            if($type=='ref'&&$oldtype!=$type){
                  echo $root->parent->name.'\\'.$root->name."该方法中的{$name}引用变量不能转变类型，请修改!".NEWLINE;
            }
            $root->tempvalue[$name] = PhpDocHelp::checkType($type,$oldtype );
        } else {
            foreach($root->tempvalue as $k=>$n){//检查是否有大小写不同但是字母相同的变量
                if(strtolower($k)==strtolower($name)){
                    echo($this->getInfo()."方法中：该变量{$name}和{$k}重名，不能仅仅是大小写不同".NEWLINE);
                    fgets(STDIN);
                    die;
                }
            }
            $root->tempvalue[$name] = $type;
        }
    }
    const canusetypes=['float','long','int','string','boolean'];
    protected function getTempValueMes(){
        $arr=[];
        foreach ($this->tempvalue as $name=>$type) {
            if(trim($name)=='')continue;
            if(!in_array($type,self::canusetypes))$type='var';
            if(key_exists($type,$arr)){
                $arr[$type].=','.$name;
            }
            else{
                $arr[$type]=$name;
            }
         }
         $mes='';
         foreach ($arr as $type=>$name) {
             $mes.='      '.$type.' '.$name.';'.NEWLINE;
         }
         return $mes;
    }

    protected function getfinalparam(){
        $parameters=$this->parameters;
       if($parameters!=null){
               for($i=0;$i<count($parameters);$i++){
                   if(key_exists($i,$this->strongtype)){
                       if(in_array($this->strongtype[$i],self::canusetypes))
                           $parameters[$i]=$this->strongtype[$i].' '.$parameters[$i];
                   }
                   else if($this->argstype!=null&&$i<count($this->argstype)){
                       if(in_array($this->argstype[$i],self::canusetypes))
                           $parameters[$i]=$this->argstype[$i].' '.$parameters[$i];
                   }
           }
       $mes=implode(',',$parameters);
       return $mes;
        }
    }
    protected function getmes()
    {
        if($this->isStatic()) $this->modifier=str_replace(['private','protected'],'public',$this->modifier);
        if(preg_match('@inline_f[\d]{6}@',$this->name))return '';
        if($this->name==EMPTYNODE){ return $this->getTempValueMes().parent::getmes();}
        $mes=parent::getmes();
        return $this->modifier.' function '.$this->name.'('.$this->getfinalparam().'){'.NEWLINE.$this->getTempValueMes().$mes.NEWLINE.'  }'.NEWLINE;
    }
    public function getinlinefunmes(){//获得参数传递的匿名方法内容
        if($this->name==EMPTYNODE){ return $this->getTempValueMes().parent::getmes();}
        $mes=parent::getmes();
//        if(preg_match('@\$this->.*@',$mes)){
//            echo('匿名函数内部不能用$this->,用静态数据替换'.NEWLINE.$mes.NEWLINE);
//            fgets(STDIN);die;
//        }
        return 'function '.'('.$this->getfinalparam().'){'.NEWLINE.$this->getTempValueMes().$mes.NEWLINE.'  }'.NEWLINE;
    }
    public function start()
    {
        parent::start();
        foreach ($this->actions as $action) {
            $action();
        }
        if(preg_match('@inline_f[\d]{6}@',$this->name)){
             preg_replace_callback('@::(\$[\w\d_]+)@',function($match){
                 $this->getparentclass()->setMemberPublic($match[1]);
             },$this->text);
        }
    }
}
class PhpNode_TryCatch extends PhpRoot{
    private $catch;
    private $finally;
    public function __construct($name,$parent,$text,$catch,$catchtext,$finally){
        parent::initialze($name,$parent,$text);
        $this->catch=$catch;
        $this->childs[0]=new PhpNode_Function(EMPTYNODE,$this,$text,'','');
        $this->childs[1]=new PhpNode_Function(EMPTYNODE,$this,$catchtext,'','');
        $this->finally=new PhpNode_Function(EMPTYNODE,$this,$finally,'','');
    }
    protected function start()
    {
        parent::start();
        $arr=explode(' ',trim($this->catch));
        if(count($arr)<2)return;
        $this->catch=$arr[0].','.$arr[1];
        $this->parent->insertTempValue($arr[1],'var');
    }

    protected function getmes()
    {
        $trymes=$this->childs[0]->getmes();
        $catchmes=$this->childs[1]->getmes();
        if(preg_match('@^\s+$@s',$trymes))return '';
        $mes='try{'.NEWLINE.$trymes.'}catch '.$this->catch.' {'.NEWLINE.$catchmes.'}';
        $f=$this->finally->getmes();
        if($f!='') {
            $mes = preg_replace_callback('@return@', function ($match)use($f){
                return $f.NEWLINE.'return';
            }, $mes);
            return $mes.NEWLINE .$f;
        }
        return $mes;
    }
}
//class PhpNode_NiMingFunc extends PhpNode_Function{
//    public function __construct($name,$parent,$text,$parameters){
//
//        parent::__construct($name,$parent,$text,'',$parameters);
//        $this->parameters=$parameters;
//    }
//   protected function getmes(){
//
//       $mes=parent::getmes();
//       return 'function'.$this->parameters.'{'.NEWLINE.$this->getTempValueMes().$mes.NEWLINE.'  }'.NEWLINE;
//   }
//}
class PhpNode_FunctionArgs extends PhpRoot {
    private $front;
    private $back;
    private $pre;
    public function __construct($name,$parent,$args,$pre)//$args 方法参数,不包括最外层的括号
    {
        $this->pre=$pre;
        $args=trim($args);
        if(in_array($name,['preg_match','preg_match_all'])) {
            $arg3=PhpDocHelp::splitArgs($this->text);
            if(count($arg3)>2)
            preg_replace_callback('@.?(\$[\w\d_]+)@', function ($match) use ($parent) {
                if ($match[0] != ':') {
                    $parent->getparentfun()->insertTempValue($match[1], 'ref');
                }
            }, $arg3[2]);
        }
        if($name=='spl_autoload_register'){//自动加载的方法必须是公共的
            $funname=str_replace('"','',$args);
            $loadarr=explode('::',$funname);
            if(count($loadarr)>1){
                $loadfun=$this->getFunByFullName($loadarr[1],$loadarr[0]);
                if($loadfun){
                    $loadfun->modifier=str_replace('private','public',$loadfun->modifier);
                }
            }
        }
        parent::initialze($name,$parent,$args);
        $this->text=preg_replace_callback('@\$this->(.*?inline_f[\d]{6}[\w\d_]*)@',function($match){
            $fun=$this->getFunByName($match[1]);
            if($fun!=null)return $fun->getinlinefunmes();
            return $match[0];
        },$args);
        $arr=explode('->',$this->text);
        if($arr!=null&&count($arr)>2){//表示是调用链

        }
        else{
            $arr=PhpDocHelp::splitArgs($this->text);
            $types=[];
            foreach ($arr as $item) {
                $item=trim($item);
                $type=$this->getparentfun()->getRunType($item);
                if($type=='none')$type=PhpDocHelp::getVarType($item,true);
                $types[]=$type;
            }
            $name=$this->name;
            $calltype=$name;
            $name1=explode('->',$name);
            if(preg_match('@^\$([\w\d_]+)$@',$name,$m)){
                $calltype=$parent->getparentfun()->getRunType($name);
                if($calltype!='none'){
                    $pc=$parent->getClassByName($calltype);
                    if($pc)
                    $fun=$pc->getFunByName('__invoke');
                    if(isset($fun)&&$fun){
                        $name.='->__invoke';
                        $this->name=$name;
                    }

                }
            }
            else if($name[0]=='$')
                $calltype=$this->getparentfun()->getRunType($name1[0]);
            if($calltype=='none') $calltype=$name;
            else $calltype=str_replace($name1[0].'->',$calltype.'::',$name);
            $arr=str_replace('$this->',$this->getparentclass()->name.'::',$calltype);
            $arr=explode('::',$arr);
            $isrefobj=strpos($args,REFTEMPOBJ)!==false;
            if(count($arr)==2){
                if(strpos($arr[0],'\\')===false)$arr[0]=$this->getparentnamespace()->name.'\\'.$arr[0];
                $fun=$this->getFunByFullName($arr[1],$arr[0]);
                if(isset($fun)&&$fun!=null){
                    $fun->insertArgsType($types);
                    $reffun=$fun->getFunByName('ref__'.$fun->name);//对引用进行处理
                    if($reffun!=null&&count($reffun->refindex)>0){
                        if($isrefobj)
                            $tempname=REFTEMPOBJ;
                        else{
                            $tempname='$reft__obj';
                            $this->front="let $tempname=(object)null;";
                        }
                        $this->getparentfun()->insertTempValue($tempname,'var');
                        if(trim($this->text)=='')$arr=[];
                        else
                        $arr=PhpDocHelp::splitArgs($this->text);
                        $newtext=[$tempname];
                        $argsnames=$fun->parameters;
                        for($i=count($arr);$i<count($argsnames);$i++){
                            $arr[]=$argsnames[$i];
                        }
                        for($i=0;$i<count($arr);$i++){
                            $argname=explode('=',$arr[$i])[0];
                            $inlinename=explode('=',substr($argsnames[$i],1))[0];
                            if(in_array($i,$reffun->refindex)){
                                $this->getparentfun()->insertTempValue($argname,'var');
                                if(!$isrefobj) {
                                    $this->front .= "let {$tempname}->$inlinename=$argname;";
                                    $this->back .= "let $argname={$tempname}->$inlinename;";
                                }
                            }
                            else {
                                $newt=explode('=',$arr[$i]);
                                $newtext[]=end($newt);
                            }
                        }
                        $this->text=implode(',',$newtext);
                        $this->name=preg_replace_callback('@'.$fun->name.'\s*$@',function($match)use($reffun){
                             return $reffun->name;
                        },$this->name);
                    }
                    else if($fun->getparentclass()->isInline()&&$fun->refindex!=null){
                        $name=preg_replace('@^(\$[\w\d_]+).*@s','$1',$name);
                        foreach ($fun->refindex as $refindex) {
                            $refname=explode('=',$fun->parameters[$refindex])[0];
                            $refname=substr($refname,1);
                            $this->back.="let \${$refname}={$name}->$refname;";
                        }
                    }
                }
            }
        }
    }

    protected function getmes()
    {
        $mes= parent::getmes();
        return $this->front.$this->pre.$this->name.'('.$this->text.$mes.');'.NEWLINE.$this->back;
    }
}
//class PhpNode_InlineFunc extends PhpRoot{
//    protected $parameters;
//    public function __construct($name,$parent,$text,$parameters)//$parameters 方法参数,不包括最外层的括号
//    {
//        $text=trim($text);
//        parent::initialze($name,$parent,$text);
//        $this->parameters=$parameters;
//        if(substr($text,0,8)=='function'){
//            $nfunname='$__func'.PhpDocHelp::getid();
//            $parent->insertTempValue($nfunname,'var');
//            $this->childs[] = new PhpNode_Function(EMPTYNODE, $this,$nfunname.'='.$text.';','','');
//            $this->text=$nfunname;
//        }
//    }
//    protected function getmes()
//    {
//        $mes= parent::getmes();
//        $qian='';
//        if(substr($this->name,-1)=='='){
//            $qian='let ';
//        }
//        $dycall=$this->name[0]=='$';
//        $mes.=$qian.$this->name.($dycall?'{':'').$this->text.($dycall?'}':'').'('.$this->parameters.');';
//        return $mes."\r\n";
//    }
//}
class Util{
    /**
     * @param $path \当该目录不存在就递归创建
     */
    public static function MkDir($path){
        if(!file_exists($path))mkdir($path,0744,true);
    }

    /**
     * @param $path \删除该目录及其下所有文件
     */
    public static function RmDir($path){
        if(file_exists($path))exec('rd /s/q "'.$path.'"');
    }

    public static function DownLoad($filepath,$showname=''){
        if(!file_exists($filepath)){
            echo($filepath."路径文件不存在");
            return;
        }
        ob_clean();
        $ne=explode('.',$showname);
        if(count($ne)>1){
            header("Content-Type:application/".$ne[count($ne)-1]);
        }
        header("Content-Disposition:attachment;filename=".$showname);
        header("Content-Length:".filesize($filepath));
        readfile($filepath);
    }
    public static function getAllPathsAndFiles($path,$recursionCount=0,$fliter=null){
        return PathHelp::GetAll($path,$recursionCount,$fliter);
    }
    public static function getFiles($path,$recursionCount=0,$fliter=null){
        return PathHelp::GetFiles($path,$recursionCount,$fliter);
    }
    public static function getDirs($path,$recursionCount=0,$fliter=null){
        return PathHelp::GetDirs($path,$recursionCount,$fliter);
    }
}
class PathHelp{
    private static function GetAllfd($path,&$arr,$recursion,callable $fliter=null,$isfile=true,$isdir=true)
    {
        if (substr($path,-1) != '/') $path .= '/';
        $dir = opendir($path);
        $file = readdir($dir);
        while ($file) {
            if ($file != '.' && $file != '..') {
                $all = $path . $file;
                $thisisdir = is_dir($all);
                $ok=($fliter==null);
                $ok=$ok||$fliter($file,$path);
                if ($thisisdir) {
                    if ($isdir&&$ok)
                        $arr[] = $all;
                } else
                {
                    if($isfile&&$ok)
                        $arr[] = $all;
                }
                if ($recursion!=0 && $thisisdir) {
                    self::GetAllfd($all . '/', $arr, $recursion-1,$fliter,$isfile,$isdir);
                }
            }
            $file = readdir($dir);
        }
    }
    public static function GetAll($path,$recursion,callable $fliter=null){
        if(!is_dir($path)){
            echo($path."该路径不存在");
        }
        $arr=[];
        self::GetAllfd($path,$arr,$recursion,$fliter);
        return $arr;
    }
    public static function GetFiles($path,$recursion,callable $fliter=null){
        if(!is_dir($path)){
            echo($path."该路径不存在");
        }
        $arr=[];
        self::GetAllfd($path,$arr,$recursion,$fliter,true,false);
        return $arr;
    }
    public static function GetDirs($path,$recursion,callable $fliter=null){
        if(!is_dir($path)){
            echo($path."该路径不存在");
        }
        $arr=[];
        self::GetAllfd($path,$arr,$recursion,$fliter,false,true);
        return $arr;
    }
}