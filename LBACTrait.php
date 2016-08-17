<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace fredyns\lbac;

use Yii;
use yii\helpers\ArrayHelper;
use fredyns\lbac\AccessControl;

/**
 * Description of LBACTrait
 *
 * @property string $label label for link
 * @property string $linkTo link to view detail
 * @property string $dropdownMenu model operastion menu
 *
 * @property AccessControl $lbac permission container
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 */
class LBACTrait
{
    protected static $_action;
    protected $_operation;

    /**
     * declaring LBAC used
     *
     * @return string
     */
    public static function lbacClass()
    {
        return AccessControl::classname();
    }

    public static function lbac()
    {
        if (empty(static::$_action))
        {
            static::$_action = Yii::createObject(static::LBAC());
        }

        return static::$_action;
    }

    public function getLbac()
    {
        if (empty($this->_operation))
        {
            $lbac = static::LBAC();

            if (is_array($lbac) == FALSE)
            {
                $lbac = ['class' => $lbac];
            }

            $lbac['model']    = $this;
            $this->_operation = Yii::createObject($lbac);
        }

        return $this->_operation;
    }

    public function getLabel()
    {
        $alternatives = ['label', 'name', 'title', 'number', 'id'];

        foreach ($alternatives as $attribute)
        {
            if ($this->model->hasAttribute($attribute))
            {
                return $this->getAttribute($attribute);
            }
        }

        return 'view '.get_called_class();
    }

    /**
     * generate link to page that show model detail
     *
     * @param string $label
     * @param array $linkOptions
     * @return string
     */
    public function getLinkTo($linkOptions = ['title' => 'view detail'])
    {
        $label = ArrayHelper::remove($linkOptions, $this->label);
        $url   = $this->lbac->url('view', $linkOptions);

        return Html::a($label, $url, $linkOptions);
    }

    public function getDropdownMenu($actions = [])
    {
        return $this->lbac->dropdownMenu($actions);
    }

}