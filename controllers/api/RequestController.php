<?php
namespace app\controllers\api;

use app\models\App\Request\RequestsDocs;
use app\models\App\RequestsPost;
use app\models\Basic\Response;
use Arhitector\Yandex\Client\Exception\UnauthorizedException;
use Arhitector\Yandex\Disk;
use Yii;


class RequestController extends BaseController
{
    public function behaviors()
    {
        return parent::behaviors();
    }

    public function actionIndex()
    {
        $post = Yii::$app->request->post();
        if(isset($post['token']) && $post['token'] == Yii::$app->params['access_request']){
            unset($post['token']);
            $new_post = new RequestsPost();
            $new_post->requests_id = -1;
            $new_post->data = json_encode($post);
            $new_post->save();

        }else{
            Yii::$app->response->setStatusCode(422);
            $response = new Response();
            $response->addError('token','Вы не указали ключ безопасности');
            return $response;
        }
        return ['msg' => 'ok'];
    }

    public function actionYandex()
    {
        return ['msg' => 'ok'];
    }

    public function actionUploadFilesToYandex()
    {
        try {
            $disk = new Disk('AQAAAAAUX1fJAAfktYg5Xkn4fUNnhCv_VTDpQK4');
            $resource = $disk->getResource('UPLOADS/FILES-FOR-REQUSETS');
            if( $resource->has() ) {
                $rows = RequestsDocs::find()->where('trash=0 and yandex=0')->limit(10)->all();
                if( !empty($rows) ) {
                    foreach ($rows as $key => $row) {
                        $file = $_SERVER['DOCUMENT_ROOT'].$row->url;
                        $new_name = '/UPLOADS/FILES-FOR-REQUSETS/'.basename($row->url);
                        $resource = $disk->getResource($new_name);
                        if( file_exists($file) && !$resource->has() ) {
                            try {
                                $resource->upload($file);
                                $resource->setPublish(true);
                                $rows[$key]->url = $resource->public_url;
                                $rows[$key]->yandex = 1;
                                $rows[$key]->save();
                                unlink($file);
                            } catch (\Exception $exception) {
                                return $exception->getMessage();
                            }
                        }
                        elseif ( !file_exists($file) ) {
                            $rows[$key]->trash = 1;
                            $rows[$key]->save();
                        }
                    }
                }
            }
            else {
                echo 'Нет возможности!';
            }
        } catch (UnauthorizedException $exc) {
            return $exc->getMessage();
        }
    }

    public function actionDeleteOldFiles()
    {
        $rows = RequestsDocs::find()->where('trash=1')->asArray()->all();
        if( empty($rows) ) return;

        foreach ($rows as $row) {
            if( file_exists($_SERVER['DOCUMENT_ROOT'].$row['url']) ){
                unlink($_SERVER['DOCUMENT_ROOT'].$row['url']);
            }
        }
    }

}