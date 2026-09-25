<?php

declare(strict_types=1);

include_once __DIR__ . '/variablePresentation.php';

trait HelperGetFloatDevice
{
    use HelperVariablePresentation;

    private static function getGetFloatCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_FLOAT) {
            return 'Float required';
        }

        return 'OK';
    }

    private static function getFloatValue($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $value = GetValue($variableID);

        $presentation = self::resolvePresentation($variableID);
        if (($presentation !== false) && ($presentation['digits'] !== null)) {
            $value = round($value, $presentation['digits']);
        }

        return $value;
    }
}

trait HelperSetFloatDevice
{
    private static function getFloatCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_FLOAT) {
            return 'Float required';
        }

        if (!HasAction($variableID)) {
            return 'Action required';
        }

        return 'OK';
    }

    private static function setFloatValue($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if (!HasAction($variableID)) {
            return false;
        }

        if ($targetVariable['VariableType'] != VARIABLETYPE_FLOAT) {
            return false;
        }

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }
}

trait HelperFloatDevice
{
    use HelperSetFloatDevice;
    use HelperGetFloatDevice;
}
