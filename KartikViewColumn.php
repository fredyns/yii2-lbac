<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace fredyns\lbac;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * Description of KartikActionColumn
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 */
class KartikViewColumn extends \kartik\grid\DataColumn
{
    /**
     * @inheritdoc
     */
    public $format = 'html';

    /**
     * Action Control class name
     *
     * @var string
     */
    public $actionControl = 'fredyns\lbac\ActionControl';

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $label         = ArrayHelper::getValue($model, $this->attribute, 'view');
        $actionControl = Yii::createObject([
                'class' => $this->actionControl,
                'model' => $model,
        ]);

        return $actionControl->getLinkTo($label);
    }

}