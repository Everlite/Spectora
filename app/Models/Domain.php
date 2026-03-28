<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected $fillable = [
        'user_id',
        'uuid',
        'url',
        'keyword_must_contain',
        'keyword_must_not_contain',
        'status_code',
        'ssl_days_left',
        'response_time',
        'safety_status',
        'safety_details',
        'visitors_today',
        'last_checked',
        'only_check_public_pages',
        'respect_robots_txt',
        'respect_noindex',
        'exclude_patterns',
        'sitemap_urls',
        'included_sitemaps',
    ];

    protected $casts = [
        'last_checked' => 'datetime',
        'notify_sent' => 'boolean',
        'last_pagespeed_details' => 'array',
        'safety_details' => 'array',
        'only_check_public_pages' => 'boolean',
        'respect_robots_txt' => 'boolean',
        'respect_noindex' => 'boolean',
        'sitemap_urls' => 'array',
        'included_sitemaps' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function history()
    {
        return $this->hasMany(ChecksHistory::class);
    }

    public function notes()
    {
        return $this->hasMany(DomainNote::class);
    }

    public function analyticsVisits()
    {
        return $this->hasMany(AnalyticsVisit::class);
    }

    public function monitoredUrls()
    {
        return $this->hasMany(MonitoredUrl::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
