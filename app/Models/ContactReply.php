<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactReply extends Model
{
    protected $fillable = ['user_id', 'from_staff', 'body'];
    public function attachments() { return $this->hasMany(ContactAttachment::class); }

    protected function casts(): array
    {
        return ['from_staff' => 'boolean'];
    }
}
