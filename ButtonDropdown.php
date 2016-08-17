<?php

namespace fredyns\lbac;

use \fredyns\lbac\AccessControl;

/**
 * Description of ButtonDropdown
 *
 * @property \yii\db\ActiveRecord $model model instance
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 */
class ButtonDropdown extends \yii\bootstrap\ButtonDropdown
{
    public static $autoIdPrefix = 'lbacButton';
    public $label               = 'Action';
    public $encodeLabel         = false;
    public $options             = ['class' => 'btn btn-primary'];
    public $align               = 'right';

    /**
     * @var \fredyns\lbac\AccessControl
     */
    public $lbac;
    public $actions = [];

    public function getId($autoGenerate = true)
    {
        if ($autoGenerate && $this->_id === null)
        {
            if ($this->model && $this->model instanceof yii\db\ActiveRecord)
            {
                $this->_id = Inflector::camel2id($this->model->tableName());

                foreach ($this->model->getPrimaryKey(true) as $value)
                {
                    $this->_id .= '_'.$value;
                }
            }
            else
            {
                $this->_id = static::$autoIdPrefix.static::$counter++;
            }
        }

        return $this->_id;
    }

    public function getModel()
    {
        return $this->lbac->model;
    }

    /**
     * Generates the dropdown menu.
     * @return string the rendering result.
     */
    protected function renderDropdown()
    {
        $config = [
            'encodeLabels'  => false,
            'clientOptions' => false,
            'view'          => $this->getView(),
            'options'       => [
                'class' => 'dropdown-menu-'.$this->align,
            ],
            'items'         => $this->items(),
        ];

        return \yii\bootstrap\Dropdown::widget($config);
    }

    /**
     * generate items parameter for dropdown menu
     *
     * @param array $items access list to be shown
     * @return array
     */
    public function items()
    {
        $params    = [];
        $count     = 0;
        $lastParam = NULL;

        foreach ($this->actions as $action)
        {
            if (is_string($action) && $action !== AccessControl::MENU_DIVIDER)
            {
                $param = $this->lbac->param($action);
                $allow = $this->lbac->allow($action);

                if ($param && $allow)
                {
                    $params[]  = $this->prepareParam($param, ['icon']);
                    $lastParam = $param;
                    $count++;
                }
            }
            else if (is_array($action) OR ( $count > 0 && $action !== $lastParam ))
            {
                $params[]  = $param;
                $lastParam = $param;
                $count++;
            }
        }

        return $params;
    }

    /**
     * prepare parameter for regular link or button
     *
     * @param Array $param
     * @param Array $use_elements used feature: icon|button
     * @return Array
     */
    public function prepareParam($param, $use_elements = [])
    {
        $icon          = ArrayHelper::remove($param, 'icon');
        $buttonOptions = ArrayHelper::remove($param, 'buttonOptions');

        if (in_array('icon', $use_elements) && $icon && isset($param['label']))
        {
            $param['label'] = $icon.' '.$param['label'];
        }

        if (in_array('button', $use_elements) && $buttonOptions && isset($param['linkOptions']))
        {
            $param['linkOptions'] = ArrayHelper::merge($param['linkOptions'], $buttonOptions);
        }

        return $param;
    }

}