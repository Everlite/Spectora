<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Prunable;

class ChecksHistory extends Model
{
    use HasFactory, Prunable;

    protected $table = 'checks_history';
    
    // WICHTIG: Wir haben timestamps (created_at, updated_at) jetzt in der DB,
    // also aktivieren wir sie hier, damit Laravel sie automatisch füllt.
    public $timestamps = true; 

    protected $guarded = [];

    // Hier sagen wir Laravel: "Behandle diese Spalten als echtes Datum"
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'response_time' => 'float', // Optional: Damit Zahlen auch Zahlen sind
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(90));
    }
}