<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->paginate(Category::withCount('events')->orderBy('id'), $request, [
            'id' => self::FILTER_INT,
            'name' => self::FILTER_LIKE,
            'description' => self::FILTER_LIKE,
            'created_at' => self::FILTER_DATE,
            'updated_at' => self::FILTER_DATE,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        return response()->json(Category::create($data), 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category->load('events'));
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string'],
        ]);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        return $this->destroyModel($category);
    }
}
