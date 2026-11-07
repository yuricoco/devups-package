<?php

/**
 * Created by PhpStorm.
 * User: Aurelien Atemkeng
 * Date: 8/17/2018
 * Time: 12:04 AM
 */
interface NotificationSerialize
{
    /**
     * return the array key => value specific for the notification purpose (the custom version of the jsonSerialize)
     * @return mixed
     */
    public function dataSerialize();
}