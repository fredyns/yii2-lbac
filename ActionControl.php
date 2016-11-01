<?php

namespace fredyns\lbac;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
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
 * @property string $allowDeleted is allowing accessing deleted page
 * @property string $allowView is allowing accessing view page
 * @property string $allowCreate is allowing accessing create page
 * @property string $allowUpdate is allowing accessing update page
 * @property string $allowDelete is allowing to delete model
 * @property string $allowRestore is allowing to restore model
 *
 * @property array $urlIndex url config for Index page
 * @property array $urlDeleted url config for Deleted page
 * @property array $urlCreate url config for Create page
 * @property array $urlView url config for View page
 * @property array $urlUpdate url config for Update page
 * @property array $urlDelete url config for Delete
 * @property array $urlRestore url config for Restore 
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
    public function allow($action, $throwError = FALSE)
    {
        if (array_key_exists($action, $this->allowed) == FALSE)
        {
            $function = 'getAllow'.ucfirst($action);

            if (method_exists($this, $function))
            {
                $this->allowed[$action] = $this->$function();
            }
            else
            {
                $this->addError($action, 'Not Allowed Action.');
                $this->allowed[$action] = FALSE;
            }
        }

        if ($this->allowed[$action] == FALSE && $throwError)
        {
            $message = $this->getError($action, TRUE);

            throw new ForbiddenHttpException($message);
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
     * check permission to access Deleted page
     *
     * @return boolean
     */
    public function getAllowDeleted()
    {
        $this->addError('deleted', "Deleted model page is not configured properly.");
        // default false karna tidak semua support
        return FALSE;
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

        if ($this->model->hasAttribute('recordStatus') && $this->model->hasAttribute('deleted_at'))
        {
            if ($this->model->getAttribute('recordStatus') == 'deleted')
            {
                $this->addError('delete', "Data already (soft) deleted.");
            }
        }

        // conclusion
        return ($this->isError('delete') == FALSE);
    }

    /**
     * check permission to restore model
     *
     * @return boolean
     */
    public function getAllowRestore()
    {
        // prerequisites
        if (($this->model instanceof ActiveRecord) == FALSE)
        {
            $this->addError('restore', "Unknown Data.");

            return FALSE;
        }

        // blacklist
        if ($this->model->isNewRecord)
        {
            $this->addError('restore', "Can't restore undeleted Data.");
        }

        if ($this->model->hasAttribute('recordStatus') == FALSE OR $this->model->hasAttribute('deleted_at') == FALSE)
        {
            $this->addError('restore', "Data doesn't support soft-delete.");
        }
        elseif ($this->model->getAttribute('recordStatus') != 'deleted')
        {
            $this->addError('restore', "Data is not deleted.");
        }

        // conclusion
        return ($this->isError('restore') == FALSE);
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

    public function getUrlDeleted()
    {
        return [
            $this->actionRoute('deleted'),
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

    public function getUrlRestore()
    {
        if ($this->model instanceof ActiveRecord)
        {
            $param       = $this->modelParam();
            $param[0]    = $this->actionRoute('restore');
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
                return ['view', 'update', 'delete', 'restore'];
            }
        }

        return ['index', 'create', 'deleted'];
    }

    /**
     * all possible actions & configuration
     *
     * @return array
     */
    public function actions()
    {
        return [
            'index'   => [
                'label'         => 'List',
                'url'           => $this->urlIndex,
                'icon'          => Icon::show('list'),
                'linkOptions'   => [
                    'title'      => 'click to open all active data',
                    'aria-label' => 'Index',
                    'data-pjax'  => '0',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-default',
                ],
            ],
            'deleted' => [
                'label'         => 'Deleted',
                'url'           => $this->urlDeleted,
                'icon'          => Icon::show('trash'),
                'linkOptions'   => [
                    'title'      => 'click to open all deleted data',
                    'aria-label' => 'Deleted',
                    'data-pjax'  => '0',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-info',
                ],
            ],
            'create'  => [
                'label'         => 'Create',
                'url'           => $this->urlCreate,
                'icon'          => Icon::show('plus'),
                'linkOptions'   => [
                    'title'      => 'click to create new record',
                    'aria-label' => 'Create',
                    'data-pjax'  => '0',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-info',
                ],
            ],
            'view'    => [
                'label'         => 'View',
                'url'           => $this->urlView,
                'icon'          => Icon::show('zoom-in'),
                'linkOptions'   => [
                    'title'      => 'click to view this data',
                    'aria-label' => 'View',
                    'data-pjax'  => '0',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-primary',
                ],
            ],
            'update'  => [
                'label'         => 'Update',
                'url'           => $this->urlUpdate,
                'icon'          => Icon::show('pencil'),
                'linkOptions'   => [
                    'title'      => 'click to edit this data',
                    'aria-label' => 'Update',
                    'data-pjax'  => '0',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-success',
                ],
            ],
            'delete'  => [
                'label'         => 'Delete',
                'url'           => $this->urlDelete,
                'icon'          => Icon::show('trash'),
                'linkOptions'   => [
                    'title'        => 'click to delete this data',
                    'aria-label'   => 'Delete',
                    'data-pjax'    => '0',
                    'data-confirm' => 'Are you sure to delete this item?',
                    'data-method'  => 'post',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-danger',
                ],
            ],
            'restore' => [
                'label'         => 'Restore',
                'url'           => $this->urlRestore,
                'icon'          => Icon::show('retweet'),
                'linkOptions'   => [
                    'title'        => 'click to restore this data',
                    'aria-label'   => 'Restore',
                    'data-pjax'    => '0',
                    'data-confirm' => 'Are you sure to restore this item?',
                ],
                'buttonOptions' => [
                    'class' => 'btn btn-info',
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
        $linkOptions = ArrayHelper::getValue($params, 'linkOptions', []);
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
                    $param['label'] = $icon.'&nbsp; '.$param['label'];
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
     * @param array $options
     * @return string
     */
    public function getLinkTo($options = [])
    {
        if (is_string($options))
        {
            $options = ['label' => $options];
        }

        $options = ArrayHelper::merge($options, ['label' => $this->modelLabel()]);

        if ($this->allow('view'))
        {
            return $this->a('view', $options);
        }

        return $options['label'];
    }

    /**
     * all breadcrumb labels
     *
     * @return array
     */
    public function breadcrumbLabels()
    {
        return [
            'index'   => 'List',
            'create'  => 'Create',
            'view'    => '#'.implode('-', $this->modelParam()),
            'update'  => 'Edit',
            'delete'  => 'Delete',
            'restore' => 'Restore',
        ];
    }

    /**
     * spesific breadcrumb label
     *
     * @return array
     */
    public function breadcrumbLabel($name)
    {
        return ArrayHelper::getValue($this->breadcrumbLabels(), $name, '#');
    }

    /**
     * generate breadcrumb link item
     * if user is not permitted to access page/url, only label will be returned
     *
     * @param string $name
     * @param array $options
     * @return array|string
     */
    public function breadcrumbItem($name, $options = [])
    {
        if ($this->allow($name))
        {
            return ArrayHelper::merge([
                    'label' => $this->breadcrumbLabel($name),
                    'url'   => $this->url($name),
                    ], $options);
        }
        else
        {
            return $this->breadcrumbLabel($name);
        }
    }

}