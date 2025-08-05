<?php

namespace Tests\Fixtures\Valid\Models\Eloquent;

use Illuminate\Notifications\Notifiable;

class User extends \Illuminate\Foundation\Auth\User
{
	use Notifiable;
}
