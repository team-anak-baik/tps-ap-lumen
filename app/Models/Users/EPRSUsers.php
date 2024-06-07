<?php

namespace App\Models\Users;

use App\Models\EPRS\Requisitions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EPRSUsers extends Model
{
    protected $connection = "connection_third";
    protected $table = "cbisms.cbisms_user";

    protected $connFirst, $connSecond, $connThird;

    public function __construct()
    {
        $this->connFirst = DB::connection('connection_first');
        $this->connSecond = DB::connection('connection_second');
        $this->connThird = DB::connection('connection_third');
    }

    public function requisitions()
    {
        return $this->hasMany(Requisitions::class, 'userid', 'cbisms_user_id');
    }
}
