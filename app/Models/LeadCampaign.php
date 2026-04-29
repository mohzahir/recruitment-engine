<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadCampaign extends Model
{
    use HasFactory;

    protected $guarded = [];

    // This allows the Dispatcher to easily pull the Lead data
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    // This allows the Dispatcher to easily pull the Email Template
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}