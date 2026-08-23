<?php

use Spatie\Permission\Models\Role;

Role::create(['name' => 'super-admin']);
Role::create(['name' => 'admin']);
Role::create(['name' => 'pharmacist']);
Role::create(['name' => 'cashier']);