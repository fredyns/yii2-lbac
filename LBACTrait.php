<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace fredyns\lbac;

use Yii;
use fredyns\lbac\Permission;

/**
 * Description of LBACTrait
 *
 * @property Permission $_action permission container
 * @property Permission $_operation permission container
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
    public static function LBAC()
    {
        return Permission::classname();
    }

    public static function action()
    {
        if (empty(static::$_action))
        {
            static::$_action = Yii::createObject(static::LBAC());
        }

        return static::$_action;
    }

    public function getOperation()
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

}