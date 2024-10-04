<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'created_by'];
    public $timestamps = false;

    public function users()
    {
        // a group can have many users in group_users table
        return $this->belongsToMany(User::class, 'group_users');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
