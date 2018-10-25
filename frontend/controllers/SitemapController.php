<?php
/**
 * Author: lf
 * Blog: https://blog.feehi.com
 * Email: job@feehi.com
 * Created at: 2016-04-02 22:48
 */

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use frontend\models\Category;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;


class SitemapController extends Controller
{

    /**
     * 单页
     *
     * @param string $name
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionView($name = '')
    {
        /*if ($name == '') {
            $name = Yii::$app->getRequest()->getPathInfo();
        }
        $model = Article::findOne(['type' => Article::SINGLE_PAGE, 'sub_title' => $name]);
        if (empty($model)) {
            throw new NotFoundHttpException('None page named ' . $name);
        }
        return $this->render('view', [
            'model' => $model,
        ]);*/
        $cate = Category::getCategories();
        $formatTree = $this->formatTree($cate);
        $sitemap = $this->procHtml($formatTree);

        return $this->render('view',[
            'sitemap'=> $sitemap
        ]);


    }

    public function formatTree($items) {
        $tree = array(); //格式化好的树
        foreach ($items as $item)
            if (isset($items[$item['parent_id']]))
                $items[$item['parent_id']]['son'][] = &$items[$item['id']];
            else
                $tree[] = &$items[$item['id']];
        return $tree;
    }


    public  function procHtml($tree)
    {
        $html = '';

        foreach($tree as $t)
        {
            if(!isset($t['son']))
            {
                $html .= "<li><a href='".Url::to(['article/index','cat'=>$t['id']])."'>{$t['name']}</a></li>";
            }
            else
            {
                $html .= "<li>"."<a href='".Url::to(['article/index','cat'=>$t['id']])."'>{$t['name']}</a>";
                $html .= $this->procHtml($t['son']);
                $html = $html."</li>";
            }
        }
        return $html ? '<ul>'.$html.'</ul>' : $html ;
    }

    /*public function getTree($data, $pId)
    {
        $html = '';
        foreach($data as $k => $v)
        {
            if($v['parent_id'] == $pId)
            {        //父亲找到儿子
                $html .= "<li>".$v['name'];
                $html .= $this->getTree($data, $v['id']);
                $html = $html."</li>";
            }
        }
        return $html ? '<ul>'.$html.'</ul>' : $html ;
    }*/

}