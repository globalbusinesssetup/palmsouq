<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $casts = [
        'rating' => 'integer',
    ];

    protected $fillable = [
        'client_name', 'testimonial', 'rating', 'status'
    ];

}
