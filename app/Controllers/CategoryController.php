<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Category;
use App\Services\ActivityLogService;

final class CategoryController extends Controller
{
    public function index(Request $request): void
    {
        $categories = new Category();
        Response::view('categories/index', [
            'title' => 'Categories',
            'categories' => $categories->listForTeam($this->teamId()),
            'role' => $this->role(),
        ]);
    }

    public function store(Request $request): void
    {
        if (!$this->can('category.manage')) {
            Response::abort(403);
        }
        $data = $request->all();
        $categories = new Category();
        $id = $categories->insert([
            'team_id' => $this->teamId(),
            'name' => trim((string) $data['name']),
            'color' => $data['color'] ?? '#4F46E5',
            'icon' => $data['icon'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        (new ActivityLogService())->log($this->teamId(), $this->userId(), 'category.created', 'category', $id, 'Category created.');
        $this->respond($request, true, 'Category created.', null, '/categories');
    }

    public function update(Request $request, string $id): void
    {
        if (!$this->can('category.manage')) {
            Response::abort(403);
        }
        $categories = new Category();
        $category = $categories->find((int) $id);
        if ($category === null || ((int) $category['team_id'] !== $this->teamId() && $category['team_id'] !== null)) {
            Response::abort(404);
        }
        $data = $request->all();
        $categories->update((int) $id, [
            'name' => trim((string) $data['name']),
            'color' => $data['color'] ?? '#4F46E5',
            'icon' => $data['icon'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
        $this->respond($request, true, 'Category updated.', null, '/categories');
    }

    public function archive(Request $request, string $id): void
    {
        if (!$this->can('category.manage')) {
            Response::abort(403);
        }
        $categories = new Category();
        $category = $categories->find((int) $id);
        if ($category === null) {
            Response::abort(404);
        }
        $categories->update((int) $id, ['is_archived' => $category['is_archived'] ? 0 : 1]);
        (new ActivityLogService())->log($this->teamId(), $this->userId(), 'category.archived', 'category', (int) $id, 'Category archive state toggled.');
        $this->respond($request, true, 'Category updated.', null, '/categories');
    }
}
