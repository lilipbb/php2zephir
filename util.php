<?php
namespace ZephirHelp;

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