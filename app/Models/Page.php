<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model {
    use HasFactory;

    public function template(){
      return $this->morphOne(Template::class, 'callable');
    }

    public function scripts(){
      return $this->morphMany(Script::class, 'callable');
    }

    public function stlyes(){
      return $this->morphMany(Style::class, 'callable');
    }
}
