<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    // This allows us to insert data without explicitly listing every single column
    protected $guarded = [];

    // This tells Laravel to treat cc_emails as an array
    protected $casts = [
        'cc_emails' => 'array',
        'last_contacted_at' => 'datetime', // Tells Laravel this is a date, not a string
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
}