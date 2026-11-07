<?php

interface WorkerCallback
{

    /**
     * return the array key => value specific for the notification purpose (the custom version of the jsonSerialize)
     * @return mixed
     */
    public function workerCompleted($payload = null);

}
