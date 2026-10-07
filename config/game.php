<?php
return [
    'board' => [
        'width' => 9,
        'height' => 9,
        'tile_size_dp' => 36,
        'min_tile_size_px' => 32,
        'max_tile_size_px' => 48,
    ],
    'lives' => [
        'max' => 5,
        'regen_hours' => 3,
        'regen_minutes' => 180,
        'overflow_enabled' => false,
    ],
    'scoring' => [
        'match_3' => 60,
        'match_4' => 150,
        'match_5' => 300,
        'match_l_t' => 200,
        'cascade_bonus_percent' => 20,
        'special_activation_base' => 100,
        'combo_multiplier_min' => 400,
        'combo_multiplier_max' => 2000,
    ],
    'moves' => [
        'base_formula' => '23 - floor(level/100)*2',
        'minimum' => 15,
        'obstacle_bonus_per_type' => 1,
        'obstacle_bonus_max' => 3,
        'tutorial_bonus_every' => 20,
        'tutorial_bonus_moves' => 3,
    ],
    'versioning' => [
        'config_version' => 1,
        'cache_ttl_seconds' => 3600,
        'force_refresh_on_mismatch' => true,
    ],
    'analytics' => [
        'enabled' => false,
        'sample_rate_percent' => 1,
    ],
];