<?php

/**
 * Utility to interact with the newrelic extension.
 */
class NewRelic
{
    /**
     * Posts a notice to New Relic, with optional custom parameters.
     *
     * @param $message
     * @param array $params
     */
    public static function sendNotice($message, array $params = [])
    {
        if (!static::isEnabled()) {
            return;
        }

        foreach ($params as $key => $value) {
            newrelic_add_custom_parameter($key, $value);
        }

        newrelic_notice_error($message);
    }

    /**
     * Posts a handled throwable to New Relic.
     *
     * @param Throwable $e
     */
    public static function sendException(Throwable $e)
    {
        if (!static::isEnabled()) {
            return;
        }

        newrelic_notice_error($e->getMessage(), $e);
    }

    /**
     * Names the current New Relic transaction.
     *
     * @param string $name
     * @param bool $captureParams
     */
    public static function nameTransaction($name, $captureParams = false)
    {
        if (!static::isEnabled() || empty($name)) {
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
        if (!static::isEnabled()) {
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
     * Posts a custom event to NewRelic.
     *
     * @param string $name
     * @param array $attributes
     */
    public static function recordCustomEvent($name, array $attributes)
    {
        if (!static::isEnabled()) {
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
     * Starts a New Relic transaction.
     *
     * @param string $appName
     */
    public static function startTransaction($appName)
    {
        if (!static::isEnabled()) {
            return;
        }

        newrelic_start_transaction($appName);
    }

    /**
     * Ends a New Relic transaction
     *
     * @param bool $ignore When set to true, it will make newrelic never report and forget the current transaction.
     */
    public static function endTransaction($ignore = false)
    {
        if (!static::isEnabled()) {
            return;
        }

        newrelic_end_transaction($ignore);
    }

    /**
     * @link https://docs.newrelic.com/docs/agents/php-agent/php-agent-api/newreliccustommetric-php-agent-api
     * @param string $metric
     * @param float $responseTime
     */
    public static function addCustomMetric($metric, $responseTime)
    {
        if (!static::isEnabled()) {
            return;
        }

        newrelic_custom_metric($metric, $responseTime);
    }

    /**
     * Sets New Relic transaction as background job
     */
    public static function setAsBackgroundJob()
    {
        if (!static::isEnabled()) {
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
     * Sets the app name under which this transaction will be tracked.
     *
     * @param string $appName
     */
    public static function setAppName($appName)
    {
        if (self::isEnabled()) {
            newrelic_set_appname((string)$appName);
        }
    }

    public static function isEnabled(): bool
    {
        return extension_loaded('newrelic');
    }
}
