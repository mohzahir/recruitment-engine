<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = ['name', 'target_role', 'status'];

    // الحملة تمتلك عدة قوالب إيميل
    public function templates()
    {
        return $this->hasMany(EmailTemplate::class);
    }

    // الحملة تمتلك عدة أشخاص (Contacts)
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
}