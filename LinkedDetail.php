<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace fredyns\lbac;

use yii\helpers\ArrayHelper;

/**
 * Description of LinkedDetail
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 */
class LinkedDetail extends \yii\base\Widget
{
    public $model;
    public $attribute;
    public $actionControl;

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (empty($this->model))
        {
            return '';
        }

        $attr  = explode('.', $this->attribute);
        $model = ArrayHelper::getValue($this->model, $attr[0]);
        $label = ArrayHelper::getValue($this->model, $this->attribute);

        if (empty($model))
        {
            return null;
        }

        if (is_scalar($label) == FALSE)
        {
            $label = 'view';
        }

        if (empty($this->actionControl))
        {
            return $label;
        }

        $actionControl = \Yii::createObject([
                'class' => $this->actionControl,
                'model' => $model,
        ]);

        if ($actionControl instanceof ActionControl)
        {
            return $actionControl->getLinkTo([
                    'label'       => $label,
                    'linkOptions' => [
                        'title'  => 'click to view this data',
                        'target' => '_blank',
                    ],
            ]);
        }

        return $label;
    }

}