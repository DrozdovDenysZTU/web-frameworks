<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

abstract class Controller
{
    protected const FILTER_INT = 'int';
    protected const FILTER_EXACT = 'exact';
    protected const FILTER_LIKE = 'like';
    protected const FILTER_NUMBER = 'number';
    protected const FILTER_DATE = 'date';

    private const DEFAULT_ITEMS_PER_PAGE = 10;
    private const MAX_ITEMS_PER_PAGE = 100;

    protected function paginate(Builder $query, Request $request, array $filters): JsonResponse
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'itemsPerPage' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_ITEMS_PER_PAGE],
            ...$this->filterRules($filters),
        ]);

        $this->applyFilters($query, $request, $filters);

        $paginator = $query->paginate(
            perPage: (int) ($request->query('itemsPerPage') ?: self::DEFAULT_ITEMS_PER_PAGE),
            page: (int) ($request->query('page') ?: 1),
        );

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'itemsPerPage' => $paginator->perPage(),
                'totalItems' => $paginator->total(),
                'totalPages' => $paginator->lastPage(),
            ],
        ]);
    }

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

    private function filterRules(array $filters): array
    {
        $rules = [];

        foreach ($filters as $param => $type) {
            match ($type) {
                self::FILTER_INT => $rules[$param] = ['nullable', 'integer', 'min:1'],
                self::FILTER_EXACT, self::FILTER_LIKE => $rules[$param] = ['nullable', 'string'],
                self::FILTER_NUMBER => $rules += [
                    $param => ['nullable', 'numeric'],
                    $param . '_min' => ['nullable', 'numeric'],
                    $param . '_max' => ['nullable', 'numeric'],
                ],
                self::FILTER_DATE => $rules += [
                    $param => ['nullable', 'date'],
                    $param . '_from' => ['nullable', 'date'],
                    $param . '_to' => ['nullable', 'date'],
                ],
            };
        }

        return $rules;
    }

    private function applyFilters(Builder $query, Request $request, array $filters): void
    {
        foreach ($filters as $param => $type) {
            $column = $query->qualifyColumn($param);

            switch ($type) {
                case self::FILTER_INT:
                case self::FILTER_EXACT:
                    if ($request->filled($param)) {
                        $query->where($column, $request->query($param));
                    }
                    break;

                case self::FILTER_LIKE:
                    if ($request->filled($param)) {
                        $query->where($column, 'like', '%' . addcslashes($request->query($param), '%_\\') . '%');
                    }
                    break;

                case self::FILTER_NUMBER:
                    foreach ([$param => '=', $param . '_min' => '>=', $param . '_max' => '<='] as $name => $operator) {
                        if ($request->filled($name)) {
                            $query->where($column, $operator, $request->query($name));
                        }
                    }
                    break;

                case self::FILTER_DATE:
                    if ($request->filled($param)) {
                        $query->whereDate($column, Carbon::parse($request->query($param))->toDateString());
                    }
                    if ($request->filled($param . '_from')) {
                        $this->whereDateBound($query, $column, '>=', $request->query($param . '_from'));
                    }
                    if ($request->filled($param . '_to')) {
                        $this->whereDateBound($query, $column, '<=', $request->query($param . '_to'));
                    }
                    break;
            }
        }
    }

    private function whereDateBound(Builder $query, string $column, string $operator, string $value): Builder
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
            ? $query->whereDate($column, $operator, $value)
            : $query->where($column, $operator, Carbon::parse($value));
    }
}
