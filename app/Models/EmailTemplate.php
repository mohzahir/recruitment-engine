<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['campaign_id', 'name', 'subject', 'body', 'step_number', 'delay_days'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}