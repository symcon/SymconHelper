<?php

declare(strict_types=1);

interface HelperPlaybackValues
{
    const PREVIOUS = 0;
    const STOP = 1;
    const PLAY = 2;
    const PAUSE = 3;
    const NEXT = 4;
}

trait HelperPlaybackDevice
{
    private static function getPlaybackCompatibility($variableID)
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

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        switch ($profileName) {
            case '~Playback':
            case '~PlaybackPreviousNext':
                break;

            default:
                return '~Playback profile required';
        }

        return 'OK';
    }

    private static function activateCommand($variableID, $value)
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

        return RequestActionEx($variableID, $value, 'VoiceControl');
    }

    private static function activatePrevious($variableID)
    {
        return self::activateCommand($variableID, HelperPlaybackValues::PREVIOUS);
    }

    private static function activatePlay($variableID)
    {
        return self::activateCommand($variableID, HelperPlaybackValues::PLAY);
    }

    private static function activatePause($variableID)
    {
        return self::activateCommand($variableID, HelperPlaybackValues::PAUSE);
    }

    private static function activateStop($variableID)
    {
        return self::activateCommand($variableID, HelperPlaybackValues::STOP);
    }

    private static function activateNext($variableID)
    {
        return self::activateCommand($variableID, HelperPlaybackValues::NEXT);
    }

    private static function supportsPreviousNext($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        return $profileName === '~PlaybackPreviousNext';
    }
}
