<?php

declare(strict_types=1);

include_once __DIR__ . '/variablePresentation.php';

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
    use HelperVariablePresentation;

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

        $presentation = self::resolvePresentation($variableID);
        if ($presentation === false) {
            return 'Presentation required';
        }

        switch ($presentation['kind']) {
            case 'legacy':
                if (!in_array($presentation['profile'], ['~Playback', '~PlaybackPreviousNext'])) {
                    return '~Playback profile required';
                }
                break;

            case 'enumeration':
                if (!self::hasPlaybackOptions($presentation['options'], [HelperPlaybackValues::STOP, HelperPlaybackValues::PLAY, HelperPlaybackValues::PAUSE])) {
                    return 'Options for Stop, Play and Pause required';
                }
                break;

            default:
                return 'Unsupported presentation';
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
        $presentation = self::resolvePresentation($variableID);
        if ($presentation === false) {
            return false;
        }

        switch ($presentation['kind']) {
            case 'legacy':
                return $presentation['profile'] === '~PlaybackPreviousNext';

            case 'enumeration':
                return self::hasPlaybackOptions($presentation['options'], [HelperPlaybackValues::PREVIOUS, HelperPlaybackValues::NEXT]);

            default:
                return false;
        }
    }

    private static function hasPlaybackOptions($options, $values)
    {
        $optionValues = array_column($options, 'Value');

        foreach ($values as $value) {
            if (!in_array($value, $optionValues)) {
                return false;
            }
        }

        return true;
    }
}
