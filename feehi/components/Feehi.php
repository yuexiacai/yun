<?php
/**
 * Author: lf
 * Blog: https://blog.feehi.com
 * Email: job@feehi.com
 * Created at: 2017-03-15 21:16
 */

namespace feehi\components;

use common\models\Category;
use feehi\cdn\DummyTarget;
use Yii;
use common\helpers\FileDependencyHelper;
use backend\components\CustomLog;
use yii\base\Component;
use backend\components\AdminLog;
use common\models\Options;
use yii\caching\FileDependency;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use yii\web\Response;

class Feehi extends Component
{

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : '';
    }


    public function init()
    {
        parent::init();

        $cache = Yii::$app->getCache();
        $key = 'options';
        if (($data = $cache->get($key)) === false) {
            $data = Options::find()->where(['type' => Options::TYPE_SYSTEM])->orwhere([
                'type' => Options::TYPE_CUSTOM,
                'autoload' => Options::CUSTOM_AUTOLOAD_YES,
            ])->asArray()->indexBy("name")->all();
            $cacheDependencyObject = Yii::createObject([
                'class' => FileDependencyHelper::className(),
                'rootDir' => '@backend/runtime/cache/file_dependency/',
                'fileName' => 'options.txt',
            ]);
            $fileName = $cacheDependencyObject->createFile();
            $dependency = new FileDependency(['fileName' => $fileName]);
            $cache->set($key, $data, 0, $dependency);
        }

        foreach ($data as $v) {
            $this->{$v['name']} = $v['value'];
        }
    }


    private static function configInit()
    {
        if (! empty(Yii::$app->feehi->website_url)) {
            Yii::$app->params['site']['url'] = Yii::$app->feehi->website_url;
        }
        if (substr(Yii::$app->params['site']['url'], -1, 1) != '/') {
            Yii::$app->params['site']['url'] .= '/';
        }
        if (stripos(Yii::$app->params['site']['url'], 'http://') !== 0 && stripos(Yii::$app->params['site']['url'], 'https://') !== 0 && stripos(yii::$app->params['site']['url'], '//')) {
            Yii::$app->params['site']['url'] = ( Yii::$app->getRequest()->getIsSecureConnection() ? "https://" : "http://" ) . yii::$app->params['site']['url'];
        }

        if (isset(Yii::$app->session['language'])) {
            Yii::$app->language = Yii::$app->session['language'];
        }
        if (Yii::$app->getRequest()->getIsAjax()) {
            Yii::$app->getResponse()->format = Response::FORMAT_JSON;
        } else {
            Yii::$app->getResponse()->format = Response::FORMAT_HTML;
        }

        if (! empty(Yii::$app->feehi->smtp_host) && ! empty(Yii::$app->feehi->smtp_username)) {
            Yii::configure(Yii::$app->mailer, [
                'useFileTransport' => false,
                'transport' => [
                    'class' => 'Swift_SmtpTransport',
                    'host' => Yii::$app->feehi->smtp_host,  //每种邮箱的host配置不一样
                    'username' => Yii::$app->feehi->smtp_username,
                    'password' => Yii::$app->feehi->smtp_password,
                    'port' => Yii::$app->feehi->smtp_port,
                    'encryption' => Yii::$app->feehi->smtp_encryption,

                ],
                'messageConfig' => [
                    'charset' => 'UTF-8',
                    'from' => [Yii::$app->feehi->smtp_username => Yii::$app->feehi->smtp_nickname]
                ],
            ]);
        }

        $cdn = Yii::$app->get('cdn');
        if( $cdn instanceof DummyTarget){
            Yii::configure(Yii::$app->cdn, [
                'host' => Yii::$app->params['site']['url']
            ]);
        }
    }

    public static function frontendInit()
    {

        if (! Yii::$app->feehi->website_status) {
            Yii::$app->catchAll = ['site/offline'];
        }
        Yii::$app->language = Yii::$app->feehi->website_language;
        Yii::$app->timeZone = Yii::$app->feehi->website_timezone;
        if (! isset(Yii::$app->params['site']['url']) || empty(Yii::$app->params['site']['url'])) {
            Yii::$app->params['site']['url'] = Yii::$app->request->getHostInfo();
        }
        if(isset(Yii::$app->session['view'])) Yii::$app->viewPath = Yii::getAlias('@frontend/view') . Yii::$app->session['view'];
        //判断是否为手机浏览器，设置对应的views目录
       /* if(self::isMobile()){

            if(isset(Yii::$app->session['view'])) {
                Yii::$app->viewPath = Yii::getAlias('@frontend/view') . Yii::$app->session['view'].'/m';
            }else{
                Yii::$app->viewPath = Yii::getAlias('@frontend/views') . '/m';
            }
            //die(Yii::$app->viewPath);
        }else{
            if(isset(Yii::$app->session['view'])) Yii::$app->viewPath = Yii::getAlias('@frontend/view') . Yii::$app->session['view'];
        }*/


        Yii::configure(Yii::$app->getUrlManager(), [
            'rules' => array_merge(Yii::$app->getUrlManager()->rules, Category::getUrlRules())
        ]);
        Yii::$app->getUrlManager()->init();


        self::configInit();
    }

    public static function isMobile() {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA'])) {
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile','MicroMessenger');
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }

    public static function backendInit()
    {
        Event::on(BaseActiveRecord::className(), BaseActiveRecord::EVENT_AFTER_INSERT, [
            AdminLog::className(),
            'create'
        ]);
        Event::on(BaseActiveRecord::className(), BaseActiveRecord::EVENT_AFTER_UPDATE, [
            AdminLog::className(),
            'update'
        ]);
        Event::on(BaseActiveRecord::className(), BaseActiveRecord::EVENT_AFTER_DELETE, [
            AdminLog::className(),
            'delete'
        ]);
        Event::on(CustomLog::className(), CustomLog::EVENT_AFTER_CREATE, [
            AdminLog::className(),
            'custom'
        ]);
        Event::on(CustomLog::className(), CustomLog::EVENT_AFTER_DELETE, [
            AdminLog::className(),
            'custom'
        ]);
        Event::on(CustomLog::className(), CustomLog::EVENT_CUSTOM, [
            AdminLog::className(),
            'custom'
        ]);
        Event::on(BaseActiveRecord::className(), BaseActiveRecord::EVENT_AFTER_FIND, function ($event) {
            if (isset($event->sender->updated_at) && $event->sender->updated_at == 0) {
                $event->sender->updated_at = null;
            }
        });
        self::configInit();
    }

}