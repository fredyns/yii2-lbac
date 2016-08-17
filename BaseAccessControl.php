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
class BaseAccessControl extends Object
{
    const WIDGET_DROPDOWN = 'dropdown';
    const WIDGET_LINK     = 'link';
    const WIDGET_BUTTON   = 'button';
    const MENU_DIVIDER    = '<li role="presentation" class="divider"></li>';

    public $errors         = [];
    public $allowed        = [];
    public $model;
    public $widget_type    = 'dropdown';
    public $link_separator = ' &centerdot; ';
    public $align          = 'right';

    public function addError($name = NULL, $message = '')
    {
        $this->error[$name][] = $message;
    }

    public function getError($name = NULL)
    {
        if (empty($name))
        {
            return $this->errors;
        }

        return ArrayHelper::getValue($this->errors, $name);
    }

    public function isError($name)
    {
        $error = ArrayHelper::getValue($this->errors, $name);

        return (empty($error) == FALSE);
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
                $this->setError($name, "Action control is not defined.");
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

    public function defaultAction()
    {
        return [];
    }

    public function param($key = '', $options = [])
    {
        $param = ArrayHelper::getValue($this->actions(), $key);

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
    public function url($name = '', $options = [])
    {
        return $this->param($name.'.url', $options);
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
        return (bool) $this->model;
    }

    public function allowUpdate()
    {
        return (bool) $this->model;
    }

    public function allowDelete()
    {
        return (bool) $this->model;
    }

    public function exception($name = '')
    {
        return new UserException(implode(chr(13), $this->getError($name)));
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

        $params = $this->param($name, $options);

        $label       = ArrayHelper::getValue($params, 'label');
        $linkOptions = ArrayHelper::getValue($params, 'options', []);
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

    /**
     * generate link if allowed
     *
     * @param String $name
     * @param Array $options
     * @return String
     */
    public function link($name, $options = [])
    {
        $allow = $this->allow($name);

        if ($allow)
        {
            return $this->a($name, $options);
        }

        return NULL;
    }

    /**
     * generate button link
     *
     * @param String $name
     * @param Array $options
     * @return String
     */
    public function btn($name, $options = [])
    {
        if (is_string($options))
        {
            $options = ['label' => $options];
        }

        $params = $this->params($name, $options);

        $text        = ArrayHelper::getValue($params, 'label');
        $linkOptions = ArrayHelper::getValue($params, 'linkOptions', []);
        $urlOptions  = ArrayHelper::getValue($params, 'urlOptions', []);

        $icon          = ArrayHelper::getValue($params, 'icon');
        $buttonOptions = ArrayHelper::getValue($params, 'buttonOptions', []);

        $label       = trim($icon.' '.$text);
        $linkOptions = ArrayHelper::merge($linkOptions, $buttonOptions);

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

    /**
     * generate button link if allowed
     *
     * @param String $name
     * @param Array $options
     * @return String
     */
    public function button($name, $options = [], $failMessage = NULL)
    {
        $allow = $this->allow($name);

        if ($allow)
        {
            return $this->btn($name, $options);
        }

        if ($failMessage !== NULL)
        {
            $errorMessage = $this->getError($name);

            $failMessage = "<span class=\"label label-warning\" alt=\"{$errorMessage}\" title=\"{$errorMessage}\">{$failMessage}</span>";
        }

        return $failMessage;
    }
    //* ================ widget ================ *//

    /**
     * generate dropdown widget
     *
     * @param array $items
     * @param array $options
     * @return string
     */
    public function dropdownMenu($actions = [])
    {
        if (empty($actions))
        {
            $actions = $this->defaultAction();
        }

        return ButtonDropdown::widget([
                'lbac'    => $this,
                'actions' => $actions,
        ]);
    }

    /**
     * generate button widget
     *
     * @param array $items
     * @return string
     */
    public function buttonMenu($items = [])
    {
        if (empty($items))
        {
            $items = $this->defaultAction();
        }

        $links = [];

        foreach ($items as $item => $options)
        {
            if (is_int($item))
            {
                $item    = $options;
                $options = [];
            }

            if ($this->allow($item))
            {
                $links[] = $this->btn($item, $options);
            }
        }

        if ($links)
        {
            $output = "<p class=\"pull-{$this->align}\">\n";

            $output .= implode("\n", $links);

            return $output."\n</p>";
        }

        return '';
    }

    /**
     * generate regular link widget
     *
     * @param array $items
     * @return string
     */
    public function linkMenu($items = [])
    {
        if (empty($items))
        {
            $items = $this->defaultAction();
        }

        $links = [];

        foreach ($items as $item => $options)
        {
            if (is_int($item))
            {
                $item    = $options;
                $options = [];
            }

            if ($this->allow($item))
            {
                $links[] = $this->a($item, $options);
            }
        }

        return ($links) ? implode($this->link_separator, $links) : '';
    }

}