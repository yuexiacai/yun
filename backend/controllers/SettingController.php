<?php
/**
 * Author: lf
 * Blog: https://blog.feehi.com
 * Email: job@feehi.com
 * Created at: 2016-03-23 12:08
 */

namespace backend\controllers;

use backend\actions\DeleteAction;
use Yii;
use backend\models\form\SettingWebsiteForm;
use backend\models\form\SettingSmtpForm;
use common\models\Options;
use yii\base\Model;
use yii\web\Response;
use yii\swiftmailer\Mailer;
use yii\web\BadRequestHttpException;
use yii\web\UnprocessableEntityHttpException;

/**
 * Setting controller
 */
class SettingController extends \yii\web\Controller
{

    public function actions()
    {
        return [
            "custom-delete" => [
                "class" => DeleteAction::className(),
                "modelClass" => Options::className(),
            ]
        ];
    }

    /**
     * 网站设置
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionWebsite()
    {
        $model = Yii::createObject( SettingWebsiteForm::className() );
        if (Yii::$app->getRequest()->getIsPost()) {
            if ($model->load(Yii::$app->getRequest()->post()) && $model->validate() && $model->setWebsiteConfig()) {
                Yii::$app->getSession()->setFlash('success', Yii::t('app', 'Success'));
            } else {
                $errors = $model->getErrors();
                $err = '';
                foreach ($errors as $v) {
                    $err .= $v[0] . '<br>';
                }
                Yii::$app->getSession()->setFlash('error', $err);
            }
        }

        $model->getWebsiteSetting();
        return $this->render('website', [
            'model' => $model
        ]);

    }

    /**
     * 自定义设置
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCustom()
    {
        $settings = Options::find()->where(['type' => Options::TYPE_CUSTOM])->orderBy("sort")->indexBy('id')->all();

        if (Model::loadMultiple($settings, Yii::$app->getRequest()->post()) && Model::validateMultiple($settings)) {
            foreach ($settings as $setting) {
                $setting->save(false);
            }
            Yii::$app->getSession()->setFlash('success', Yii::t('app', 'Success'));
        }
        $options = Yii::createObject( Options::className() );
        $options->loadDefaultValues();

        return $this->render('custom', [
            'settings' => $settings,
            'model' => $options,
        ]);
    }

    /**
     * 增加自定义设置项
     *
     * @return array|string
     * @throws UnprocessableEntityHttpException
     * @throws \yii\base\InvalidConfigException
     */

    public function actionCustomCreate()
    {
        if( Yii::$app->getRequest()->getIsAjax() ){
            Yii::$app->getResponse()->format = Response::FORMAT_JSON;
        }
        /** @var Options $model */
        $model = Yii::createObject( Options::className() );
        $model->type = Options::TYPE_CUSTOM;
        if( Yii::$app->getRequest()->getIsPost() ) {
            if ($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
                Yii::$app->getSession()->setFlash('success', Yii::t('app', 'Success'));
                return [];
            } else {
                $errors = $model->getErrors();
                $err = '';
                foreach ($errors as $v) {
                    $err .= $v[0] . '<br>';
                }
                throw new UnprocessableEntityHttpException($err);
            }
        }else{
            $this->layout = false;
            $model->loadDefaultValues();
            return $this->render("custom-create", [
                'model' => $model,
            ]);
        }
    }

    /**
     * 修改自定义设置项
     *
     * @param string $id
     * @return array
     * @throws UnprocessableEntityHttpException
     */
    public function actionCustomUpdate($id = '')
    {
        if( Yii::$app->getRequest()->getIsAjax() ){
            Yii::$app->getResponse()->format = Response::FORMAT_JSON;
        }
        $model = Options::findOne(['id' => $id]);
        if (Yii::$app->getRequest()->getIsPost()) {
            if ($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
                Yii::$app->getSession()->setFlash('success', Yii::t('app', 'Success'));
                return [];
            } else {
                $errors = $model->getErrors();
                $err = '';
                foreach ($errors as $v) {
                    $err .= $v[0] . '<br>';
                }
                throw new UnprocessableEntityHttpException($err);
            }
        } else {
            $this->layout = false;
            return $this->render("custom-update", [
                'model' => $model,
            ]);
        }
    }

    /**
     * 邮件smtp设置
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSmtp()
    {
        $model = Yii::createObject( SettingSmtpForm::className() );
        if (Yii::$app->getRequest()->getIsPost()) {
            if ($model->load(Yii::$app->getRequest()->post()) && $model->validate() && $model->setSmtpConfig()) {
                Yii::$app->getSession()->setFlash('success', Yii::t('app', 'Success'));
            } else {
                $errors = $model->getErrors();
                $err = '';
                foreach ($errors as $v) {
                    $err .= $v[0] . '<br>';
                }
                Yii::$app->getSession()->setFlash('error', $err);
            }
        }

        $model->getSmtpConfig();
        return $this->render('smtp', [
            'model' => $model
        ]);

    }

    /**
     * 发送测试邮件确认smtp设置是否正确
     *
     * @return mixed
     * @throws BadRequestHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionTestSmtp()
    {
        $model = Yii::createObject( SettingSmtpForm::className() );
        Yii::$app->getResponse()->format = Response::FORMAT_JSON;
        if ($model->load(Yii::$app->getRequest()->post()) && $model->validate()) {
            $mailer = Yii::createObject([
                'class' => Mailer::className(),
                'useFileTransport' => false,
                'transport' => [
                    'class' => 'Swift_SmtpTransport',
                    'host' => $model->smtp_host,
                    'username' => $model->smtp_username,
                    'password' => $model->smtp_password,
                    'port' => $model->smtp_port,
                    'encryption' => $model->smtp_encryption,

                ],
                'messageConfig' => [
                    'charset' => 'UTF-8',
                    'from' => [$model->smtp_username => $model->smtp_nickname]
                ],
            ]);
            return $mailer->compose()
                ->setFrom($model->smtp_username)
                ->setTo($model->smtp_username)
                ->setSubject('Email SMTP test ' . Yii::$app->name)
                ->setTextBody('Email SMTP config works successful')
                ->send();
        } else {
            $error = '';
            foreach ($model->getErrors() as $item) {
                $error .= $item[0] . "<br/>";
            }
            throw new BadRequestHttpException( $error );
        }
    }

}
