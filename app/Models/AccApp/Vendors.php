<?php

namespace App\Models\AccApp;

use Illuminate\Database\Eloquent\Model;

class Vendors extends Model
{
    protected $connection = "connection_second";
    protected $table = "accapptps2023.ap_vendor";

    public function invoices()
    {
        return $this->hasMany(Invoices::class, 'vendcode', 'vendcode');
    }
}
