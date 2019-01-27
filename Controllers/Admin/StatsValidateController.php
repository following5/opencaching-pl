<?php

namespace Controllers\Admin;

use Controllers\BaseController;
use lib\Objects\Stats\StatFields;

/**
 * Verify and repair redundant statistics and similar data in database
 */

class StatsValidateController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function isCallableFromRouter($actionName)
    {
        return $actionName != 'objectUri';
    }

    public function index()
    {
        $this->securityCheck();
        $this->buildTemplateView();
    }

    /**
     * Evaluate submitted form data and run the requested check or repair
     */
    public function run()
    {
        $this->securityCheck();

        if (isset($_REQUEST['check'])) {
            $action = 'check';
        } elseif (isset($_REQUEST['repair'])) {
            $action = 'repair';
        } else {
            throw new \Exception('unknown action');
        }

        $results = [];
        foreach ($_REQUEST as $param => $value) {
            if (substr($param, 0, 3) == 'sv-' && $value == 'on') {

                // Dots in form parameter names don't work, so we encoded them as '|'.
                $field = str_replace('|', '.', substr($param, 3));

                $validatorClass = StatFields::validatorClass($field);
                $validator = new $validatorClass;
                $results[$field] = call_user_func(
                    [$validator, 'validate'],
                    $field,
                    $action == 'repair'
                );
            }
        }
        $this->buildTemplateView($results);
    }

    public static function objectUri($field, $id)
    {
        $validatorClass = StatFields::validatorClass($field);
        $objectType = call_user_func([$validatorClass, 'objectType']);
        return self::getObjectUri($objectType, $id);
    }

    private function buildTemplateView($results = null)
    {
        $this->view->setVar('statsFields', StatFields::fields());
        $this->view->setVar('results', $results);

        if ($this->ocConfig->inDebugMode()) {
            $this->view->setVar('message', tr('admin_statcheck_develmsg'));
        } else {
            $this->view->setVar('message', '');
        }

        $this->view->setTemplate('sysAdmin/statsValidate');
        $this->view->loadJQuery();
        $this->view->buildView();
    }

    private  function securityCheck()
    {
        if (!$this->userIsLoggedSysAdmin()) {
            $this->view->redirect('/');
            exit();
        }
    }

}
