<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::forUser($request->user()->id)
            ->with(['transactions' => function ($query) use ($request) {
                $query->forUser($request->user()->id);
            }])
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories)
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        try {
            // Cek batasan jumlah kategori
            if (!$request->user()->canCreateCategory()) {
                return response()->json([
                    'message' => 'You have reached the maximum number of categories. Upgrade to premium for unlimited categories.',
                    'upgrade_required' => true
                ], 403);
            }

            $category = Category::create(array_merge(
                $request->validated(),
                ['user_id' => $request->user()->id]
            ));

            return response()->json([
                'message' => 'Category created successfully',
                'data' => new CategoryResource($category)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create category: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create category',
                'error' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    public function show(Category $category)
    {
        $this->authorize('view', $category);
        
        return response()->json([
            'data' => new CategoryResource($category)
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        try {
            $this->authorize('update', $category);

            $category->update($request->validated());

            return response()->json([
                'message' => 'Category updated successfully',
                'data' => new CategoryResource($category)
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to update this category.',
            ], 403);

        } catch (\Exception $e) {
            Log::error('Failed to update category: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to update category.',
                'error' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);
        
        if (!$category->canBeDeleted()) {
            return response()->json([
                'message' => 'Cannot delete category that has transactions or is a default category'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
