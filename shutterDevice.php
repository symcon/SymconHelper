<?php

declare(strict_types=1);
define('OPEN', 0);
define('CLOSE', 4);

trait HelperShutterDevice
{
    private static function getShutterCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */) {
            return 'Integer required';
        }

        if (!HasAction($variableID)) {
            return 'Action required';
        }

        if (!function_exists('IPS_GetVariablePresentation')) {
            $profileName = '';
            if ($targetVariable['VariableCustomProfile'] != '') {
                $profileName = $targetVariable['VariableCustomProfile'];
            } else {
                $profileName = $targetVariable['VariableProfile'];
            }

            if (!in_array($profileName, ['~ShutterMoveStop', '~ShutterMoveStep'])) {
                return '~ShutterMoveStop or ~ShutterMoveStep profile required';
            }
        } else {
            $presentation = IPS_GetVariablePresentation($variableID);
            if (empty($presentation)) {
                return 'Presentation required';
            }

            if ($presentation['PRESENTATION'] != VARIABLE_PRESENTATION_LEGACY || ($presentation['PRESENTATION'] == VARIABLE_PRESENTATION_LEGACY && !in_array($presentation['PROFILE'], ['~ShutterMoveStop', '~ShutterMoveStep']))) {
                return '~ShutterMoveStop or ~ShutterMoveStep profile required';
            }
        }

        return 'OK';
    }

    private static function getShutterOpen($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_INTEGER) {
            return false;
        }

        $value = GetValueInteger($variableID);

        return $value == OPEN;
    }

    private static function setShutterOpen($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if (!HasAction($variableID)) {
            return false;
        }

        if ($targetVariable['VariableType'] != 1 /* Integer */) {
            return false;
        }

        $triggerValue = $value ? OPEN : CLOSE;

        return RequestActionEx($variableID, $triggerValue, 'VoiceControl');
    }
}
