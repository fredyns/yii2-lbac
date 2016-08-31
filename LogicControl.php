<?php

namespace fredyns\lbac;

use Yii;
use yii\helpers\ArrayHelper;
use cornernote\returnurl\ReturnUrl;
use kartik\icons\Icon;

/**
 * Description of Permission
 *
 * @property yii\db\ActiveRecord $model
 *
 * @author fredy
 */
class LogicControl extends \fredyns\lbac\BaseLogicControl
{

    public function defaultAction()
    {
        return ($this->model) ? ['view', 'update', 'delete'] : ['index', 'create'];
    }

    public function actions()
    {
        $params = [];

        if ($this->model && $this->model instanceof yii\db\ActiveRecord)
        {
            $params = $this->model->getPrimaryKey(true);
        }

        $params['ru'] = ReturnUrl::getToken();

        return [
            'index'  => [
                'label'         => 'List',
                'url'           => [
                    $this->route('index'),
                    'ru' => $params['ru'],
                ],
                'icon'          => Icon::show('list'),
                'buttonOptions' => [
                    'class' => 'btn btn-default',
                ],
            ],
            'create' => [
                'label'         => 'Create',
                'url'           => [
                    $this->route('create'),
                    'ru' => $params['ru'],
                ],
                'icon'          => Icon::show('plus'),
                'buttonOptions' => [
                    'class' => 'btn btn-info',
                ],
            ],
            'view'   => [
                'label'         => 'View',
                'url'           => ArrayHelper::merge([$this->route('view')], $params),
                'icon'          => Icon::show('eye'),
                'buttonOptions' => [
                    'class' => 'btn btn-primary',
                ],
            ],
            'update' => [
                'label'         => 'Update',
                'url'           => ArrayHelper::merge([$this->route('update')], $params),
                'icon'          => Icon::show('pencil', ['style' => 'color: blue;']),
                'buttonOptions' => [
                    'class' => 'btn btn-success',
                ],
            ],
            'delete' => [
                'label'         => 'Delete',
                'url'           => ArrayHelper::merge([$this->route('delete')], $params),
                'icon'          => Icon::show('trash', ['style' => 'color: red;']),
                'options'       => [
                    'data-confirm' => 'Are you sure to delete this item?',
                    'data-method'  => 'post',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-danger',
                ],
            ],
        ];
    }

    public function allowIndex()
    {
        return true;
    }

    public function allowCreate()
    {
        return true;
    }

    public function allowView()
    {
        // prerequisites
        if (empty($this->model) OR ( $this->model instanceof yii\db\ActiveRecord) == FALSE)
        {
            $this->addError('view', "Model is not an ActiveRecord.");

            return false;
        }

        // blacklist
        if ($this->model->isNewRecord)
        {
            $this->setError('view', "Cann't view unsaved Data.");
        }

        // conclusion
        return ($this->isError('view') == FALSE);
    }

    public function allowUpdate()
    {
        // prerequisites
        if (empty($this->model) OR ( $this->model instanceof yii\db\ActiveRecord) == FALSE)
        {
            $this->addError('update', "Model is not an ActiveRecord.");

            return false;
        }

        // blacklist
        if ($this->model->isNewRecord)
        {
            $this->setError('update', "Cann't view unsaved Data.");
        }

        // conclusion
        return ($this->isError('update') == FALSE);
    }

    public function allowDelete()
    {
        // prerequisites
        if (empty($this->model) OR ( $this->model instanceof yii\db\ActiveRecord) == FALSE)
        {
            $this->addError('delete', "Model is not an ActiveRecord.");

            return false;
        }

        // blacklist
        if ($this->model->isNewRecord)
        {
            $this->setError('delete', "Cann't view unsaved Data.");
        }

        // conclusion
        return ($this->isError('delete') == FALSE);
    }

    public function yiiActionColumn()
    {
        return [
            'class'          => 'yii\grid\ActionColumn',
            'contentOptions' => ['nowrap' => 'nowrap'],
            'content'        => function ($model, $key, $index, $column)
        {
            if (method_exists($model, 'getDropdownMenu'))
            {
                return $model->getDropdownMenu();
            }

            return 'LBAC not set';
        },
        ];
    }

    public function kartikActionColumn()
    {
        return [
            'class'          => 'kartik\grid\ActionColumn',
            'contentOptions' => ['nowrap' => 'nowrap'],
            'content'        => function ($model, $key, $index, $column)
        {
            if (method_exists($model, 'getDropdownMenu'))
            {
                return $model->getDropdownMenu();
            }

            return 'LBAC not set';
        },
        ];
    }

}