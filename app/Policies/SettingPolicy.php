<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Setting;
use App\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Setting  $setting
     * @return mixed
     */
    public function view(User $user)
    {
        $permission = Permission::where('name', 'setting_system')->first();
        return $user->hasRole($permission->roles);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        $permission = Permission::where('name', 'setting_system')->first();
        return $user->hasRole($permission->roles);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Setting  $setting
     * @return mixed
     */
    public function update(User $user)
    {
        $permission = Permission::where('name', 'setting_system')->first();
        return $user->hasRole($permission->roles);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Setting  $setting
     * @return mixed
     */
    public function delete(User $user)
    {
        $permission = Permission::where('name', 'setting_system')->first();
        return $user->hasRole($permission->roles);
    }

    public function sms_settings(User $user)
    {
        $permission = Permission::where('name', 'sms_settings')->first();
        return $user->hasRole($permission->roles);
    }

    public function pos_settings(User $user)
    {
        $permission = Permission::where('name', 'pos_settings')->first();
        return $user->hasRole($permission->roles);
    }

    public function payment_gateway(User $user)
    {
        $permission = Permission::where('name', 'payment_gateway')->first();
        return $user->hasRole($permission->roles);
    }

    public function mail_settings(User $user)
    {
        $permission = Permission::where('name', 'mail_settings')->first();
        return $user->hasRole($permission->roles);
    }


    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Setting  $setting
     * @return mixed
     */
    public function restore(User $user)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Setting  $setting
     * @return mixed
     */
    public function forceDelete(User $user)
    {
        //
    }
}
