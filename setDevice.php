<?php

declare(strict_types=1);

trait HelperSetDevice
{
    private static function setDevice($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        if (!HasAction($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }
}
