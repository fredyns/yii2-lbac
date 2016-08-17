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
class AccessControl extends \fredyns\lbac\BaseAccessControl
{

    public function defaultAction()
    {
        return ($this->model) ? ['view', 'update', 'delete'] : ['index', 'create'];
    }

    public function actions()
    {
        $params = [
            'ru' => ReturnUrl::getToken(),
        ];

        if ($this->model && $this->model instanceof yii\db\ActiveRecord)
        {
            foreach ($this->model->primaryKey() as $attribute)
            {
                $params[$attribute] = $this->model->getAttribute($attribute);
            }
        }

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

}