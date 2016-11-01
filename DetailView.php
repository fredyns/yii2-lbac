<?php

namespace fredyns\lbac;

use yii\helpers\ArrayHelper;

/**
 * Description of DetailView
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 */
class DetailView extends \yii\widgets\DetailView
{
    public $timeZone = 'Asia/Jakarta';
    public $profileActControl;

    /**
     * @inheritdoc
     */
    protected function normalizeAttributes()
    {
        if (is_array($this->attributes))
        {
            foreach ($this->attributes as $i => $attribute)
            {
                $blamed    = ArrayHelper::getValue($attribute, 'blamed');
                $timestamp = ArrayHelper::getValue($attribute, 'timestamp');

                if ($blamed && $timestamp)
                {
                    $this->attributes[$i]['value'] = '-';
                }
            }
        }

        return parent::normalizeAttributes();
    }

    /**
     * @inheritdoc
     */
    protected function renderAttribute($attribute, $index)
    {
        $blamed         = ArrayHelper::getValue($attribute, 'blamed');
        $timestamp      = ArrayHelper::getValue($attribute, 'timestamp');
        $linkActControl = ArrayHelper::getValue($attribute, 'linkActControl');

        if ($linkActControl)
        {
            $attribute['format'] = 'raw';
            $attribute['value']  = LinkedDetail::widget([
                    'model'         => $this->model,
                    'attribute'     => $attribute['attribute'],
                    'actionControl' => $linkActControl,
            ]);
        }
        elseif ($blamed && $timestamp)
        {
            $attribute['format'] = 'raw';
            $attribute['value']  = BlamingDetail::widget([
                    'model'         => $this->model,
                    'blamed'        => $blamed,
                    'timestamp'     => $timestamp,
                    'timeZone'      => $this->timeZone,
                    'actionControl' => $this->profileActControl,
            ]);
        }

        return parent::renderAttribute($attribute, $index);
    }

}