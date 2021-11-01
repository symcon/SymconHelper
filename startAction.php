<?php

declare(strict_types=1);

trait HelperStartAction
{
    private static function getActionCompatibility($action)
    {
        $actionObject = json_decode($action, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'Invalid JSON';
        }
        if (!isset($actionObject['actionID'])) {
            return 'ActionID missing';
        }
        if (!isset($actionObject['parameters'])) {
            return 'Parameters missing';
        }
        if (!in_array($actionObject['actionID'], array_column(json_decode(IPS_GetActions(), true), 'id'))) {
            return 'Action not existing';
        }
        if (!isset($actionObject['parameters']['TARGET'])) {
            return 'No target defined';
        }
        if (!IPS_ObjectExists($actionObject['parameters']['TARGET'])) {
            return 'Target missing';
        }

        return 'OK';
    }

    private static function startAction($action, $parentID)
    {
        if (self::getActionCompatibility($action) !== 'OK') {
            return false;
        }

        $actionObject = json_decode($action, true);
        $actionObject['PARENT'] = $parentID;
        $actionObject['ENVIRONMENT'] = 'VoiceControl';

        return IPS_RunAction($actionObject['actionID'], $actionObject['parameters']);
    }
}
