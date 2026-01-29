<?php

namespace App\Enums;

enum HomeOfficePolicyType: string {
    case FULL_REMOTE = 'full_remote';
    case FLEXIBLE = 'flexible';
    case LIMITED = 'limited';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::LIMITED => __('Limited (e.g., X days per Y period)'),
            self::FLEXIBLE => __('Flexible (no fixed limit)'),
            self::FULL_REMOTE => __('Full Remote (always Home Office)'),
            self::NONE => __('No Home Office'),
        };
    }
}
