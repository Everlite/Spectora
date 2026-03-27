<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainNote extends Model
{
    protected $fillable = ['domain_id', 'content', 'user_id'];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
