<?php

namespace fredyns\lbac;

use Yii;
use yii\helpers\ArrayHelper;
use fredyns\lbac\LogicControl;

/**
 * attach LBAC to model
 *
 * @property string $label label for link
 * @property string $linkTo link to view detail
 * @property string $dropdownMenu model operastion menu
 *
 * @property LogicControl $lbac permission container
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 */
trait LBACTrait
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
        return LogicControl::classname();
    }

    /**
     * get logic control for model scope (in static mode)
     *
     * @return LogicControl
     */
    public static function lbac()
    {
        if (empty(static::$_action))
        {
            static::$_action = Yii::createObject(static::LBAC());
        }

        return static::$_action;
    }

    /**
     * get logic control for model instance
     *
     * @return LogicControl
     */
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

    /**
     * get model label/name 
     *
     * @return string
     */
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