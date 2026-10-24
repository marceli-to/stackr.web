<?php

use Illuminate\Support\Facades\Schedule;

// The server's cron runs `schedule:run` every minute (see progress.md, "Production").
Schedule::command('app:send-due-reminders')->everyFifteenMinutes()->withoutOverlapping();
