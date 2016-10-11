<?php

namespace fredyns\lbac;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
USE yii\web\ForbiddenHttpException;
USE yii\web\NotFoundHttpException;
use yii\base\UserException;
use cornernote\returnurl\ReturnUrl;
use kartik\icons\Icon;

/**
 * Description of ActionControl
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 *
 * @property string $allowIndex is allowing accessing index page
 * @property string $allowView is allowing accessing view page
 * @property string $allowCreate is allowing accessing create page
 * @property string $allowUpdate is allowing accessing update page
 * @property string $allowDelete is allowing accessing delete model
 * 
 * @property array $urlIndex url config for Index page
 * @property array $urlCreate url config for Create page
 * @property array $urlView url config for View page
 * @property array $urlUpdate url config for Update page
 * @property array $urlDelete url config for Delete page
 *
 * @property string $linkTo link o view detail
 */
class ActionControl extends \yii\base\Object
{
    const MENU_DIVIDER = '<li role="presentation" class="divider"></li>';

    /**
     * @var ActiveRecord
     */
    public $model;

    /**
     * @var array buffering action permission. useful for complex checking.
     */
    public $allowed = [];

    /**
     * @var array store any error messages
     */
    public $errors = [];

    public function addError($name, $message)
    {
        $this->errors[$name][] = $message;
    }

    public function getError($name, $asString = false)
    {
        $msg = ArrayHelper::getValue($this->errors, $name, []);

        if ($asString)
        {
            return implode("<br/>\n", $msg);
        }

        return $msg;
    }

    public function isError($name)
    {
        return (bool) $this->getError($name);
    }

    public function exception($name, $code = null)
    {
        $message = $this->getError($name, TRUE);

        if (is_null($code) == FALSE)
        {
            return new UserException($message, $code);
        }
        elseif (Yii::$app->user->isGuest)
        {
            return new NotFoundHttpException($message);
        }
        else
        {
            return new ForbiddenHttpException($message);
        }
    }

    /**
     * check permission for an action.
     * using buffer as addition.
     *
     * @param string $action
     * @return boolean
     */
    public function allow($action)
    {
        if (isset($this->allowed[$action]) == FALSE)
        {
            $function = 'getAllow'.ucfirst($action);

            if (method_exists($this, $function))
            {
                $this->allowed[$action] = $this->$function();
            }
            else
            {
                return FALSE;
            }
        }

        return $this->allowed[$action];
    }

    /**
     * check permission to access index page
     *
     * @return boolean
     */
    public function getAllowIndex()
    {
        return TRUE;
    }

    /**
     * check permission to create model
     *
     * @return boolean
     */
    public function getAllowCreate()
    {
        return TRUE;
    }

    /**
     * check permission to view model detail
     *
     * @return boolean
     */
    public function getAllowView()
    {
        // prerequisites
        if (($this->model instanceof ActiveRecord) == FALSE)
        {
            $this->addError('view', "Unknown Data.");

            return FALSE;
        }

        // blacklist
        if ($this->model->isNewRecord)
        {
            $this->addError('view', "Can't view unsaved Data.");
        }

        // conclusion
        return ($this->isError('view') == FALSE);
    }

    /**
     * check permission to update model
     *
     * @return boolean
     */
    public function getAllowUpdate()
    {
        // prerequisites
        if (($this->model instanceof ActiveRecord) == FALSE)
        {
            $this->addError('update', "Unknown Data.");

            return FALSE;
        }

        // blacklist
        if ($this->model->isNewRecord)
        {
            $this->addError('update', "Can't update unsaved Data.");
        }

        // conclusion
        return ($this->isError('update') == FALSE);
    }

    /**
     * check permission to delete model
     *
     * @return boolean
     */
    public function getAllowDelete()
    {
        // prerequisites
        if (($this->model instanceof ActiveRecord) == FALSE)
        {
            $this->addError('delete', "Unknown Data.");

            return FALSE;
        }

        // blacklist
        if ($this->model->isNewRecord)
        {
            $this->addError('delete', "Can't delete unsaved Data.");
        }

        // conclusion
        return ($this->isError('delete') == FALSE);
    }

    /**
     * get complete controller route
     *
     * @return string
     */
    public function controllerRoute()
    {
        return '';
    }

    /**
     * get route to action
     *
     * @param string $action
     * @return string
     */
    public function actionRoute($action = '')
    {
        $controller = $this->controllerRoute();

        if ($controller)
        {
            $action = '/'.$controller.'/'.$action;
        }

        return $action;
    }

    public function modelParam()
    {
        if ($this->model instanceof ActiveRecord)
        {
            return $this->model->getPrimaryKey(TRUE);
        }

        return [];
    }

    public function url($name)
    {
        $function = 'getUrl'.ucfirst($name);

        if (method_exists($this, $function))
        {
            return $this->$function();
        }
        else
        {
            return [];
        }
    }

    public function getUrlIndex()
    {
        return [
            $this->actionRoute('index'),
            'ru' => ReturnUrl::getToken(),
        ];
    }

    public function getUrlCreate()
    {
        return [
            $this->actionRoute('create'),
            'ru' => ReturnUrl::getToken(),
        ];
    }

    public function getUrlView()
    {
        if ($this->model instanceof ActiveRecord)
        {
            $param       = $this->modelParam();
            $param[0]    = $this->actionRoute('view');
            $param['ru'] = ReturnUrl::getToken();

            return $param;
        }

        return [];
    }

    public function getUrlUpdate()
    {
        if ($this->model instanceof ActiveRecord)
        {
            $param       = $this->modelParam();
            $param[0]    = $this->actionRoute('update');
            $param['ru'] = ReturnUrl::getToken();

            return $param;
        }

        return [];
    }

    public function getUrlDelete()
    {
        if ($this->model instanceof ActiveRecord)
        {
            $param       = $this->modelParam();
            $param[0]    = $this->actionRoute('delete');
            $param['ru'] = ReturnUrl::getToken();

            return $param;
        }

        return [];
    }

    /**
     * get default action list
     *
     * @return array
     */
    public function actionDefault()
    {
        if ($this->model instanceof ActiveRecord)
        {
            if ($this->model->isNewRecord == FALSE)
            {
                return ['view', 'update', 'delete'];
            }
        }

        return ['index', 'create'];
    }

    /**
     * all possible actions & configuration
     *
     * @return array
     */
    public function actions()
    {
        return [
            'index'  => [
                'label'         => 'List',
                'url'           => $this->urlIndex,
                'icon'          => Icon::show('list'),
                'options'       => [
                    'title'      => 'Search this data',
                    'aria-label' => 'Index',
                    'data-pjax'  => '0',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-default',
                ],
            ],
            'create' => [
                'label'         => 'Create',
                'url'           => $this->urlCreate,
                'icon'          => Icon::show('plus'),
                'options'       => [
                    'title'      => 'Create this data',
                    'aria-label' => 'Create',
                    'data-pjax'  => '0',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-info',
                ],
            ],
            'view'   => [
                'label'         => 'View',
                'url'           => $this->urlView,
                'icon'          => Icon::show('eye'),
                'options'       => [
                    'title'      => 'View this data',
                    'aria-label' => 'View',
                    'data-pjax'  => '0',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-primary',
                ],
            ],
            'update' => [
                'label'         => 'Update',
                'url'           => $this->urlUpdate,
                'icon'          => Icon::show('pencil', ['style' => 'color: blue;']),
                'options'       => [
                    'title'      => 'Update this data',
                    'aria-label' => 'Update',
                    'data-pjax'  => '0',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-success',
                ],
            ],
            'delete' => [
                'label'         => 'Delete',
                'url'           => $this->urlDelete,
                'icon'          => Icon::show('trash', ['style' => 'color: red;']),
                'options'       => [
                    'title'        => 'Delete this data',
                    'aria-label'   => 'Delete',
                    'data-pjax'    => '0',
                    'data-confirm' => 'Are you sure to delete this item?',
                    'data-method'  => 'post',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-danger',
                ],
            ],
        ];
    }

    /**
     * get parameter for an action
     *
     * @param string $key
     * @param array $options additional/overide parameter
     * @return array
     */
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

        $allow  = $this->allow($name);
        $params = $this->param($name, $options);

        $label       = ArrayHelper::getValue($params, 'label');
        $linkOptions = ArrayHelper::getValue($params, 'options', []);
        $urlOptions  = ArrayHelper::getValue($params, 'urlOptions', []);

        if ($allow)
        {
            $url = ArrayHelper::merge($params['url'], $urlOptions);
        }
        else
        {
            $url                  = '#';
            $linkOptions['title'] = $this->getError($name, TRUE);
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

        $allow  = $this->allow($name);
        $params = $this->param($name, $options);

        $icon          = ArrayHelper::getValue($params, 'icon');
        $text          = ArrayHelper::getValue($params, 'label');
        $urlOptions    = ArrayHelper::getValue($params, 'urlOptions', []);
        $linkOptions   = ArrayHelper::getValue($params, 'linkOptions', []);
        $buttonOptions = ArrayHelper::getValue($params, 'buttonOptions', []);

        $label       = trim($icon.' '.$text);
        $linkOptions = ArrayHelper::merge($linkOptions, $buttonOptions);

        if ($allow)
        {
            $url = ArrayHelper::merge($params['url'], $urlOptions);
        }
        else
        {
            $url                  = '#';
            $linkOptions['title'] = $this->getError($name, TRUE);
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
    public function button($name, $options = [])
    {
        $allow = $this->allow($name);

        if ($allow)
        {
            return $this->btn($name, $options);
        }

        return NULL;
    }

    /**
     * generate button widget
     *
     * @param array $items
     * @return string
     */
    public function buttons($items = [])
    {
        if (empty($items))
        {
            $items = $this->actionDefault();
        }

        $buttons = [];

        foreach ($items as $item => $options)
        {
            if (is_int($item))
            {
                $item    = $options;
                $options = [];
            }

            if ($this->allow($item))
            {
                $buttons[] = $this->btn($item, $options);
            }
        }

        if ($buttons)
        {
            return implode("\n", $buttons);
        }

        return '';
    }

    /**
     * generate items parameter for dropdown menu
     *
     * @param array $items access list to be shown
     * @return array
     */
    public function dropdownItems($items = [])
    {
        if (empty($items))
        {
            $items = $this->actionDefault();
        }

        $params    = [];
        $count     = 0;
        $lastParam = NULL;

        foreach ($items as $item)
        {
            if (is_string($item) && $item !== static::MENU_DIVIDER)
            {
                $allow = $this->allow($item);
                $param = $this->param($item);

                if ($param && $allow)
                {
                    $icon           = ArrayHelper::remove($param, 'icon');
                    $param['label'] = $icon.' '.$param['label'];
                    $params[]       = $param;
                    $lastParam      = $param;
                    $count++;
                }
            }
            else if (is_array($item) OR ( $count > 0 && $item !== $lastParam ))
            {
                $params[]  = $param;
                $lastParam = $param;
                $count++;
            }
        }

        return $params;
    }

    /**
     * generate dropdown widget
     *
     * @param array $items
     * @param array $options
     * @return string
     */
    public function dropdown($config = [])
    {
        if ($this->model instanceof ActiveRecord)
        {
            $elementId = Inflector::camel2id($this->model->tableName());
            $elementId .= '_'.implode('_', $this->modelParam());
        }
        else
        {
            $elementId = get_called_class().'_'.$this->id;
        }

        $items        = ArrayHelper::getValue($config, 'items', $this->actionDefault());
        $buttonConfig = [
            'id'          => $elementId,
            'encodeLabel' => false,
            'label'       => 'Action',
            'dropdown'    => [
                'options'      => [
                    'class' => 'dropdown-menu-'.ArrayHelper::getValue($config, 'align', 'right'),
                ],
                'encodeLabels' => false,
                'items'        => $this->dropdownItems($items),
            ],
            'options'     => [
                'data-pjax' => '0',
                'class'     => 'btn btn-primary',
            ],
        ];

        $options = ArrayHelper::getValue($config, 'options');

        if ($options)
        {
            $buttonConfig = ArrayHelper::merge($buttonConfig, $options);
        }

        /* dropdown menu */
        return \yii\bootstrap\ButtonDropdown::widget($buttonConfig);
    }

    /**
     * get model label
     *
     * @return string
     */
    public function modelLabel()
    {
        $alternatives = ['label', 'name', 'title', 'number', 'id'];

        foreach ($alternatives as $attribute)
        {
            if ($this->model->hasAttribute($attribute))
            {
                return $this->model->getAttribute($attribute);
            }
        }

        return 'view';
    }

    /**
     * generate link to page that show model detail
     *
     * @param array $linkOptions
     * @return string
     */
    public function getLinkTo($linkOptions = ['title' => 'view detail'])
    {
        $label = ArrayHelper::remove($linkOptions, 'label', $this->modelLabel());

        if ($this->allow('view'))
        {
            return Html::a($label, $this->urlView, $linkOptions);
        }
        else
        {
            return $label;
        }
    }

}