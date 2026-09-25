<?php

declare(strict_types=1);

include_once __DIR__ . '/variablePresentation.php';

interface HelperShutterValues
{
    const OPEN = 0;
    const CLOSE = 4;
}

trait HelperShutterDevice
{
    use HelperVariablePresentation;

    private static function getShutterCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_INTEGER) {
            return 'Integer required';
        }

        if (!HasAction($variableID)) {
            return 'Action required';
        }

        $presentation = self::resolvePresentation($variableID);
        if ($presentation === false) {
            return 'Presentation required';
        }

        if (($presentation['kind'] != 'legacy') || !in_array($presentation['profile'], ['~ShutterMoveStop', '~ShutterMoveStep'])) {
            return '~ShutterMoveStop or ~ShutterMoveStep profile required';
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

        return $value == HelperShutterValues::OPEN;
    }

    private static function setShutterOpen($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        if (!HasAction($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_INTEGER) {
            return false;
        }

        $triggerValue = $value ? HelperShutterValues::OPEN : HelperShutterValues::CLOSE;

        return RequestActionEx($variableID, $triggerValue, 'VoiceControl');
    }
}
