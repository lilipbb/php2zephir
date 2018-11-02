php转换zephir(php5.6,7.x)
============
#### 支持把一个目录下的.php后缀的文件全部翻译成zephir
安装和使用方式
============
```bash
git clone https://github.com/lilipbb/php2zephir
测试：在下载目录打开cmd，输入php test.php 会生成zephir文件到testphp\build文件夹里头
```
```bash
使用：
<?php
include "php2zephir.php";
\Php2Zephir\ChangeDir("php目录","zephir输出目录",递归次数);

递归次数:-1表示该目录及子目录下的所有php文件，>=0的时候则表示子目录的递归次数
```
该版本特性
========
```bash
1，函数参数支持引用传递，如function A(&$a){}
2, 支持匿名方法和use，如 $fun=function()use($a,$b){}
3, use参数也支持引用传递，如$fun=function($a,$b)use(&$c,$d){}
4, 支持全部条件表达式，如for,while,do while,if,elseif,else,foreach，switch
5，支持try，catch，finally写法
6，支持list($a,$b)写法
7，支持string各种，比如"{$a}hello",<<<Eto...等
8，方法和返回值可以用强类型，如 function A(string a,int b):int{}

ps，把有相互关联的php放在一个目录下一次性导出，这样才能支持引用传递
ps，导出的目录会按照zephir的要求分类好
```
该版本目前需要注意
---------------
```bash
1,不支持while条件内的赋值,比如while($file=opend($dir))，但支持while($a++)这种赋值
2,遇到对象方法找不到的时候，请改为 $a="funname";$b->$a();这种形式
3,暂不支持and和or比如$a=connect() or die;
4,常量数据名称必须全部大写,比如 LANG_SPLIT
5,int类型为32位有符号,超过21亿的数字会溢出
6,substr的行为有变化,如果在php中返回'',则在zephir中返回false
7,__FILE__和__DIR__不能用
8,$str[$i]应该写成substr($str,$i,1)这样的形式
9,不支持@方法来取消报错，会忽略掉@
10,file类型的资源一定要回收，不然内存会泄露
11,判定匿名方法strpos(get_class($obj),'Closure__')!==false
```
