<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('solar:sync')->everyMinute()->withoutOverlapping();
