<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::resolved(function ($broadcast): void {
    $broadcast->channel('App.Models.User.{id}', function ($user, $id) {
        return (int) $user->id === (int) $id;
    });
});
