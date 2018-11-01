php转换zephir(php5.6,7.x)
============
#### 支持把一个目录下的.php后缀的文件全部翻译成zephir
使用方式
============
```bash
\Php2Zephir\ChangeDir("php目录","zephir输出目录",递归次数);
递归次数:-1表示该目录及子目录下的所有php文件，>=0的时候则表示子目录的递归次数
```
该版本特性
========
```bash
1，支持大部分的php语法，包括引用传递，递归，局部方法，use引用等，可以转化成zephir支持的语法
2，把有相互关联的php放在一个目录下一次性导出，这样才能支持引用传递
3，导出的目录会按照zephir的要求分类好
```
该版本目前需要注意
---------------
```bash
1,不支持while条件内的赋值,比如while($file=opend($dir))
2,遇到方法找不到的时候，请改为 $a="funname";$b->$a();这种形式
3,不支持and和or比如$a=connect() or die;
4,常量数据名称必须没有小写字母,比如 LANG_SPLIT
5,int类型为32位有符号,超过21亿的数字会溢出
6,substr的行为有变化,如果在php中返回'',则在zephir中返回false
7,__FILE__和__DIR__不能用
8,$str[$i]应该写成substr($str,$i,1)这样的形式
9,不支持@方法来取消报错
10,file类型的资源一定要回收，不然内存会泄露
11,判定匿名方法strpos(get_class($obj),'Closure__')!==false
```
