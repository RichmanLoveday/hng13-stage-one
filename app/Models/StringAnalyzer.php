<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StringAnalyzer extends Model
{
    protected $table = 'string_analyzers';

    protected $fillable = [
        'hash_value',
        'input_string',
        'is_palindrome',
        'length',
        'word_count',
        'unique_character_count',
        'character_frequency_map',
    ];

    protected $casts = [
        'is_palindrome' => 'boolean',
        'character_frequency_map' => 'array',
    ];
}
