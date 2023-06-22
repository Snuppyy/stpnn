<?php
/**
 * Created by PhpStorm.
 * User: nicea
 * Date: 08.02.2019
 * Time: 0:21
 */

namespace app\widgets;
use app\components\CrmColumn;
use yii\base\InvalidConfigException;
use yii\grid\Column;
use yii\grid\GridView;
use yii\helpers\Html;
use Yii;
use yii\i18n\Formatter;

class CrmGrid extends GridView
{


    public function renderTableRow($model, $key, $index)
    {
        $cells = [];
        /* @var $column Column */
        foreach ($this->columns as $column) {
            $cells[] = $column->renderDataCell($model, $key, $index);
        }
        if ($this->rowOptions instanceof \Closure) {
            $options = call_user_func($this->rowOptions, $model, $key, $index, $this);
        } else {
            $options = $this->rowOptions;
        }
        $options['data-key'] = is_array($key) ? json_encode($key) : (string) $key;

        return Html::tag('tr', implode('', $cells), $options);
    }



}