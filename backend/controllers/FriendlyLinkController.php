<?php
/**
 * Author: lf
 * Blog: https://blog.feehi.com
 * Email: job@feehi.com
 * Created at: 2017-03-15 21:16
 */

namespace backend\controllers;

use Yii;
use backend\actions\ViewAction;
use backend\models\search\FriendlyLinkSearch;
use backend\models\FriendlyLink;
use backend\actions\CreateAction;
use backend\actions\UpdateAction;
use backend\actions\IndexAction;
use backend\actions\DeleteAction;
use backend\actions\SortAction;

/**
 * FriendLink controller
 */
class FriendlyLinkController extends \yii\web\Controller
{

    public function actions()
    {
        return [
            'index' => [
                'class' => IndexAction::className(),
                'data' => function(){
                    /** @var $searchModel FriendlyLinkSearch */
                    $searchModel = Yii::createObject( FriendlyLinkSearch::className() );
                    $dataProvider = $searchModel->search(Yii::$app->getRequest()->getQueryParams());
                    return [
                        'dataProvider' => $dataProvider,
                        'searchModel' => $searchModel,
                    ];
                }
            ],
            'create' => [
                'class' => CreateAction::className(),
                'modelClass' => FriendlyLink::className(),
            ],
            'update' => [
                'class' => UpdateAction::className(),
                'modelClass' => FriendlyLink::className(),
            ],
            'delete' => [
                'class' => DeleteAction::className(),
                'modelClass' => FriendlyLink::className(),
            ],
            'sort' => [
                'class' => SortAction::className(),
                'modelClass' => FriendlyLink::className(),
            ],
            'view-layer' => [
                'class' => ViewAction::className(),
                'modelClass' => FriendlyLink::className(),
            ],
        ];
    }

}
