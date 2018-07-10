<?php

/**
 * Utility to interact with New Relic
 */
class NewRelic
{
    /**
     * Current index of parameter stacks by their key
     * @var [string => int]
     */
    protected static $stackedParameterCount = [];

    /**
     * Will send a notice error to New Relic described by $message, with optional custom parameters.
     *
     * @param $message
     * @param array $params
     */
    public static function sendNotice($message, array $params = array())
    {
        // newrelic extension required
        if (! static::isEnabled()) {
            return;
        }

        // set params if provided
        foreach ($params as $key => $value) {
            newrelic_add_custom_parameter($key, $value);
        }

        // trigger
        newrelic_notice_error($message);
    }

    /**
     * Records an exception in New Relic. Useful for when New Relic would not normally
     * receive the exception
     *
     * @param \Exception $e
     */
    public static function sendException(\Exception $e)
    {
        if (! static::isEnabled()) {
            return;
        }

        newrelic_notice_error($e->getMessage(), $e);
    }

    /**
     * Sets the name of the transaction.
     *
     * @param string $name
     * @param bool   $captureParams
     */
    public static function nameTransaction($name, $captureParams = false)
    {
        if (! static::isEnabled() || empty($name)) {
            return;
        }

        newrelic_name_transaction($name);

        if ($captureParams) {
            newrelic_capture_params();
        }
    }

    /**
     * Adds a custom parameter to the current New Relic transaction.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function addParameterToCurrentTransaction($key, $value = 1)
    {
        if (! static::isEnabled()) {
            return;
        }

        // `newrelic_add_custom_parameter` only accepts types boolean|float|integer|string, so anything else is encoded.
        if (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
            $value = json_encode($value);
        }

        newrelic_add_custom_parameter($key, $value);
    }

    /**
     * Add an array of custom parameters to the current New Relic transaction
     *
     * @param array $parameters
     * @param string $parameterKeyPrefix
     */
    public static function addParametersToCurrentTransaction($parameters, $parameterKeyPrefix = '')
    {
        foreach ($parameters as $parameterKey => $parameterValue) {
            static::addParameterToCurrentTransaction($parameterKeyPrefix . $parameterKey, $parameterValue);
        }
    }

    /**
     * Record a custom event to NewRelic
     * **** CustomEvent on NewRelic exists on the same level as `Transaction`,
     * **** so this let's send data to NewRelic independently of the current transaction.
     *
     * @param string $name
     * @param array $attributes
     */
    public static function recordCustomEvent($name, $attributes)
    {
        if (! static::isEnabled()) {
            return;
        }

        foreach ($attributes as &$attribute) {
            // `newrelic_record_custom_event` only accepts types float|integer|string, so anything else is encoded.
            if (!is_string($attribute) && !is_numeric($attribute)) {
                $attribute = json_encode($attribute);
            }
        }

        newrelic_record_custom_event($name, $attributes);
    }

    /**
     * Set newrelic transaction as background job
     */
    public static function setAsBackgroundJob()
    {
        if (! static::isEnabled()) {
            return;
        }

        newrelic_background_job(true);
    }

    /**
     * Keeps a transaction from being reported to New Relic.
     */
    public static function ignoreTransaction()
    {
        static::isEnabled() && newrelic_ignore_transaction();
    }

    /**
     * Set the app name under which this transaction will be tracked
     *
     * @param string $appName
     */
    public static function setAppName($appName)
    {
        if (self::isEnabled()) {
            newrelic_set_appname(sprintf("%s;PHP Application", $appName));
        }
    }

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        return extension_loaded('newrelic');
    }
}
