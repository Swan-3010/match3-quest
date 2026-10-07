<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Life regeneration check every minute
Schedule::command('lives:regen')->everyMinute();

// Cleanup old sessions daily
Schedule::command('sessions:cleanup')->daily();

// Reset daily streaks at midnight
Schedule::command('daily:reset-streaks')->dailyAt('00:00');
