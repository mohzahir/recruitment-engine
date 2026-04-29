<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{

    protected $fillable = [
        'lead_id', 'campaign_id', 'full_name', 'job_title', 'email', 
        'linkedin_url', 'phone_number', 'status', 'source',
        'current_step', 'last_emailed_at', 'dormant_until'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
    
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}