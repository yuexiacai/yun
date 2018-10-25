<?php
/**
 * Author: lf
 * Blog: https://blog.feehi.com
 * Email: job@feehi.com
 * Created at: 2016-04-03 00:15
 */

namespace frontend\models;

use common\models\Category as CommonCategroy;

class Category extends CommonCategroy
{

    public static function getCatgoryLinks($cateid){


        $cate = CommonCategroy::find()->asArray()->all();


       // print_r($cate);
       // die($parentid);

        $arrCate = Category::get_top_parentid($cate,$cateid);

        return $arrCate;
    }

    public static function get_top_parentid($cate,$id){
        $arr=array();
        foreach($cate as $v){
            if($v['id']==$id){
                $arr[]=$v;// $arr[$v['id']]=$v['name'];
                $arr=array_merge(Category::get_top_parentid($cate,$v['parent_id']),$arr);
            }
        }
        return $arr;

    }
}