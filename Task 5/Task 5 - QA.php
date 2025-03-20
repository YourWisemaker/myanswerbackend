<?php

Schedule::command('app:example-command')
    ->withoutOverlapping()
    ->hourly()
    ->onOneServer()
    ->runInBackground();
