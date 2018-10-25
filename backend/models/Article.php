<?php
/**
 * Author: lf
 * Blog: https://blog.feehi.com
 * Email: job@feehi.com
 * Created at: 2016-04-11 09:53
 */

namespace backend\models;

use common\helpers\analysisWord\analysisWord;
use Yii;
use common\helpers\Util;
use common\libs\Constants;
use common\models\meta\ArticleMetaTag;

class Article extends \common\models\Article
{
    /**
     * @var string
     */
    public $tag = '';

    /**
     * @var null|string
     */
    public $content = null;


    /**
     * @inheritdoc
     */
    public function afterValidate()
    {
        if($this->visibility == Constants::ARTICLE_VISIBILITY_SECRET){//加密文章需要设置密码
            if( empty( $this->password ) ){
                $this->addError('password', Yii::t('app', "Secret article must set a password"));
            }
        }
        parent::afterValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $insert = $this->getIsNewRecord();
        Util::handleModelSingleFileUpload($this, 'thumb', $insert, '@thumb', ['thumbSizes'=>self::$thumbSizes]);
        if(empty($this->seo_title)){$this->seo_title=$this->title;}
        if(empty($this->seo_description)){
           // $this->seo_description = mb_substr(trim(strip_tags($this->content), chr(0xc2).chr(0xa0)),0,80,'utf-8');
            $contentCleaned = strip_tags($this->content);
            $contentCleaned = str_replace('&nbsp;','',$contentCleaned);
            $contentCleaned =trim($contentCleaned, chr(0xc2).chr(0xa0));

            $this->seo_description = mb_substr($contentCleaned,0,80,'utf-8');
        }
        //如果没有关键词，尝试从标签中复制，如果也没有，则进行分词操作
        if(empty($this->seo_keywords)){
            if(!empty($this->tag)){
                $this->seo_keywords = $this->tag;
            }else{
                if(isset($contentCleaned)){
                    $data = $this->title.$this->title.$this->title.$contentCleaned;
                }else{
                    $contentCleaned = strip_tags($this->content);
                    $contentCleaned = str_replace('&nbsp;','',$contentCleaned);
                    $contentCleaned =trim($contentCleaned, chr(0xc2).chr(0xa0));
                    $data = $this->title.$this->title.$this->title.$contentCleaned;
                }
                //$data = $this->title.$this->title.$this->title.strip_tags($this->content);
                analysisWord::$loadInit=false;
                $pa = new analysisWord('utf-8', 'utf-8', false);
                $pa->LoadDict ();
                $pa->SetSource ( $data );
                $pa->StartAnalysis ( true );
                $tags = $pa->GetFinallyKeywords ( 5 );
                $this->seo_keywords = $tags;
            }
        }else{
            $this->seo_keywords = str_replace('，', ',', $this->seo_keywords);
        }


        if ($insert) {
            $this->author_id = Yii::$app->getUser()->getIdentity()->getId();
            $this->author_name = Yii::$app->getUser()->getIdentity()->username;
        }
        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        $articleMetaTag = new ArticleMetaTag();
        $articleMetaTag->setArticleTags($this->id, $this->tag);
        if ( $insert ) {
            $contentModel = yii::createObject( ArticleContent::className() );
            $contentModel->aid = $this->id;
        } else {
            if ( $this->content === null ) {
                return true;
            }
            $contentModel = ArticleContent::findOne(['aid' => $this->id]);
            if ($contentModel == null) {
                $contentModel = yii::createObject( ArticleContent::className() );
                $contentModel->aid = $this->id;
            }
        }
        $contentModel->content = $this->content;
        $contentModel->save();
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        if( !empty( $this->thumb ) ){
            Util::deleteThumbnails(Yii::getAlias('@frontend/web') . $this->thumb, self::$thumbSizes, true);
        }
        Comment::deleteAll(['aid' => $this->id]);
        if (($articleContentModel = ArticleContent::find()->where(['aid' => $this->id])->one()) != null) {
            $articleContentModel->delete();
        }
        return parent::beforeDelete();
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->tag = call_user_func(function(){
            $tags = '';
            foreach ($this->articleTags as $tag) {
                $tags .= $tag->value . ',';
            }
            return rtrim($tags, ',');
        });
        $this->content = ArticleContent::findOne(['aid' => $this->id])['content'];
        parent::afterFind();
    }

}