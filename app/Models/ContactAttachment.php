<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class ContactAttachment extends Model
{
    protected $fillable = ['contact_reply_id','path','mime'];

    public function viewUrl(): string
    {
        $params = ['message'=>$this->contact_message_id,'attachment'=>$this->id];
        if (request()->routeIs('contact.guest.*')) return URL::signedRoute('contact.guest.attachment',$params);
        return route(request()->routeIs('admin.*') ? 'admin.contacts.attachment' : 'contact.attachment',$params);
    }
}
