<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function destroyModel(Model $model): JsonResponse
    {
        try {
            $model->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1451) {
                return response()->json([
                    'message' => 'Record cannot be deleted because it is referenced by other records.',
                ], 409);
            }

            throw $e;
        }

        return response()->json(null, 204);
    }
}
