<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    

    public function index(): JsonResponse
    {
        $roles = Role::all();
        return response()->json($roles, 200);
    }
}
