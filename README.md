Php Convert To Zephir (require php5.6,7.x)
============
####  Support the translation of all the *.php  files in a directory to zephir
Install & Usage
============
```bash
git clone https://github.com/lilipbb/php2zephir
Test：Open cmd in download directory，Input php test.php ,Zephir files will be generated into the testphp\build folder.
```
```bash
Usage：
<?php
include "php2zephir.php";
\Php2Zephir\ChangeDir("phpDir","zephirDir",recursionCount);

recursionCount:-1(Represents all the PHP files under the directory and subdirectory)，>=0(the number of subdirectories recursion)
```
This version features
========
```bash
1, function parameters support reference transfer, such as  
      function A (&$a) {}.
2, support anonymous methods and use, such as 
      $fun=function () use ($a, $b) {}.
3, the use parameter also supports reference transfer, such as
      $fun=function ($a, $b) use (&$c, $d) {}.
4, support all conditional expressions, such as for, while, do while, if, elseif, else, foreach, switch
5, support try, catch and finally.
6, support list ($a, $b) .
7, support all kinds of string, such as 
      $a="{$a}hello", $b=<<<Eto...Eto;
8, methods and return values can be strongly typed, such as 
      function A (string a, int b): int{}

PS, when the interrelated PHP is placed in a directory, it can be exported at once and support reference attribute.

PS, the exported directory will be sorted according to the requirements of Zephir.
```
This version needs attention at present.
---------------
```bash
1, do not support assignment in the while condition, such as while ($file=opend ($dir)), but support while ($a++) assignment.
2, when object method cannot be found, please change  to $a= "funname"; $b->$a (); 
3, do not support and and or such as $a=connect () or die;
4, const data names must be capitalized, such as LANG_SPLIT.
5, the int type is 32 bit signed, the big numbers will overflow.
6, the function behavior of substr changes, if it returns ' ' in PHP, it returns false in Zephir.
7, __FILE__ and __DIR__ can not be used.
8, $str[$i] should be change to substr ($str, $i, 1).
9, do not support the @method to cancel the report error, it will ignore the @
10, file type resources must be recycled, otherwise memory will leak.
11, determine the anonymous method strpos (get_class ($obj),'Closure__')! ==false
```
