该版本目前需要注意
1,不支持while条件内的赋值,比如while($file=opend($dir))
2,遇到方法找不到的时候，请改为 $a="funname";$b->$a();这种形式
3,不支持and和or比如$a=connect() or die;
4,常量数据名称必须没有小写字母,比如 LANG_SPLIT
5,int类型为32位有符号,超过21亿的数字会溢出
6,substr的行为有变化,在php中返回'',则在zephir中返回false
7,__FILE__和__DIR__不能用
8,类中的直属成员不能用.链接
9,最好不要使用static类成员，会导致更新插入安全问题
10,$str[$i]应该写成substr($str,$i,1)这样的形式
11,不支持@方法来取消报错
12,file类型的资源一定要回收，不然内存会泄露
13,判定匿名方法strpos(get_class($obj),'Closure__')!==false
