<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace fredyns\lbac;

use Yii;

/**
 * Description of KartikActionColumn
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 */
class KartikActionColumn extends \kartik\grid\ActionColumn
{
    /**
     * @inheritdoc
     */
    public $header = 'Action';

    /**
     * @inheritdoc
     */
    public $template = '{actionmenu}';

    /**
     * @inheritdoc
     */
    public $headerOptions = ['class' => 'skip-export'];

    /**
     * @inheritdoc
     */
    public $contentOptions = ['class' => 'skip-export'];

    /**
     * Action Control class name
     *
     * @var string
     */
    public $actionControl = 'fredyns\lbac\ActionControl';

    /**
     * configuration for ActionMenu widget
     *
     * @var array
     */
    public $menuOptions = [];

    /**
     * @inheritdoc
     */
    protected function initDefaultButtons()
    {
        // siapa tau ada yg nekat mau ganti template output. wkwk
        parent::initDefaultButtons();

        if (!isset($this->buttons['actionmenu']))
        {
            $this->buttons['actionmenu'] = function ($url, $model, $key)
            {
                $actionControl = Yii::createObject([
                        'class' => $this->actionControl,
                        'model' => $model,
                ]);

                return $actionControl->dropdown($this->menuOptions);
            };
        }
    }

}