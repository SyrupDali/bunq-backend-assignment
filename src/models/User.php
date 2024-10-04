<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['username'];
    public $timestamps = false; // disable created_at and updated_at

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function groups()
    {
        // a user can belong to many groups in group_user table
        return $this->belongsToMany(Group::class, 'group_user'); 
    }
}