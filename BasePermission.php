<?php

namespace fredyns\lbac;

use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\base\UserException;

/**
 * Base class for permission
 *
 * @property \yii\db\ActiveRecord $model model instance
 * @property array $error error message storage for model instance
 * @property array $allowed permission storage for model instance
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 */
class BasePermission extends Object
{
    const WIDGET_DROPDOWN = 'dropdown';
    const WIDGET_LINK     = 'link';
    const WIDGET_BUTTON   = 'button';

    public $errors  = [];
    public $allowed = [];
    public $model;

    public function error($name = NULL)
    {
        if (empty($name))
        {
            return $this->errors;
        }

        return ArrayHelper::getValue($this->errors, $name);
    }

    public function allow($name = null)
    {
        if (empty($name))
        {
            return $this->allowed;
        }

        if (isset($this->allowed[$name]) == FALSE)
        {
            $function = 'allow'.Inflector::id2camel($name);

            if (function_exists([$this, $function]))
            {
                $this->allowed[$name] = $this->$function();
            }
            else
            {
                $this->error[$name]   = "Model operation is not defined.";
                $this->allowed[$name] = FALSE;
            }
        }

        return $this->allowed[$name];
    }

    public function controller()
    {
        return '';
    }

    public function route($action = '')
    {
        $controller = $this->controller();

        if ($controller)
        {
            $action = '/'.$controller.'/'.$action;
        }

        return $action;
    }

    public function actions()
    {
        return [];
    }

    public function operations()
    {
        return [];
    }

    public function defaultAction()
    {
        return [];
    }

    public function defaultOperations()
    {
        return [];
    }

    public function defaultItems()
    {
        if (isset($this->model))
        {
            return $this->defaultOperations();
        }
        else
        {
            return $this->defaultOperations();
        }
    }

    public function param($source = [], $key = '', $options = [])
    {
        $param = ArrayHelper::getValue($source, $key);

        if ($param && is_array($param) && $options && is_array($options))
        {
            $param = ArrayHelper::merge($param, $options);
        }

        return $param;
    }

    /**
     * get url parameter for particular operation
     *
     * @param String $name
     * @param Array $options
     * @return Array
     */
    public function actionUrl($name = '', $options = [])
    {
        return $this->param($this->actions(), $name.'.url', $options);
    }

    /**
     * get url parameter for particular operation
     *
     * @param String $name
     * @param Array $options
     * @return Array
     */
    public function operationUrl($name = '', $options = [])
    {
        return $this->param($this->operations(), $name.'.url', $options);
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
        return isset($this->model);
    }

    public function allowUpdate()
    {
        return isset($this->model);
    }

    public function allowDelete()
    {
        return isset($this->model);
    }

    public function exception($name = '')
    {
        return new UserException($this->error($name));
    }
    //* ================ hyperlink ================ *//

    /**
     * generate regular link
     *
     * @param String $name
     * @param Array $options
     * @return String
     */
    public function a($name, $options = [])
    {
        if (is_string($options))
        {
            $options = ['label' => $options];
        }

        $params = $this->params($name, $options);

        $label       = ArrayHelper::getValue($params, 'label');
        $linkOptions = ArrayHelper::getValue($params, 'linkOptions', []);
        $urlOptions  = ArrayHelper::getValue($params, 'urlOptions', []);

        $allow = $this->allow($name);

        if ($allow)
        {
            $url = ArrayHelper::merge($params['url'], $urlOptions);
        }
        else
        {
            $url                  = '#';
            $linkOptions['title'] = $this->getError($name);
        }

        return Html::a($label, $url, $linkOptions);
    }

}